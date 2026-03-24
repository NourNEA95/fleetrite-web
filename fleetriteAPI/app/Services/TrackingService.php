<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class TrackingService
{
    /**
     * Get processed route data including stops and drives.
     * Equivalent to the legacy getRoute function.
     */
    public function getRoute($imei, $dtf, $dtt, $minStopDurationMinutes = 5, $filter = true, $userSettings = null)
    {
        $accuracy = $this->getObjectAccuracy($imei);
        
        $result = [
            'route' => [],
            'stops' => [],
            'drives' => [],
            'events' => [],
            'route_length' => 0,
            'top_speed' => 0,
            'avg_speed' => 0,
            'fuel_consumption' => 0,
            'fuel_cost' => 0,
            'stops_duration_time' => 0,
            'drives_duration_time' => 0,
            'engine_work_time' => 0,
            'engine_idle_time' => 0,
        ];

        // 1. Get raw route with timezone conversion
        $route = $this->getRouteRaw($imei, $accuracy, $dtf, $dtt, $userSettings);
        
        if (empty($route)) {
            return $result;
        }

        // 2. Filter jumping coordinates (SNAP logic instead of REMOVE)
        if ($filter) {
            $route = $this->removeRouteJunkPoints($route, $accuracy);
        }
        
        $result['route'] = $route;

        // 3. Get sensors and settings
        $accParam = $this->getSensorParamByType($imei, 'acc') ?? 'acc';
        $odoSensor = $this->getSensorByType($imei, 'odo');
        $fuelSensors = $this->getSensorsByType($imei, 'fuel');
        $fuelConsSensors = $this->getSensorsByType($imei, 'fuelcons');
        $fcr = $this->getObjectFCR($imei);
        $accuracyType = $accuracy['type'] ?? '';

        // 4. Identify Stops (matching legacy logicExactly)
        if (($accuracy['stops'] ?? 'gps') === 'gpsacc') {
            $result['stops'] = $this->getRouteStopsGPSACC($route, $accuracy, $fcr, $fuelSensors, $fuelConsSensors, $minStopDurationMinutes, $accParam, $accuracyType);
        } else if (($accuracy['stops'] ?? 'gps') === 'acc') {
            $result['stops'] = $this->getRouteStopsACC($route, $accuracy, $fcr, $fuelSensors, $fuelConsSensors, $minStopDurationMinutes, $accParam, $accuracyType);
        } else {
            $result['stops'] = $this->getRouteStopsGPS($route, $accuracy, $fcr, $fuelSensors, $fuelConsSensors, $minStopDurationMinutes, $accParam, $accuracyType);
        }

        // 5. Identify Drives
        $result['drives'] = $this->getRouteDrives($route, $accuracy, $result['stops'], $fcr, $fuelSensors, $fuelConsSensors, $accParam, $odoSensor, $accuracyType);

        // 6. Calculate Totals (matching legacy summations)
        $avg_speed_sum = 0;
        foreach ($result['drives'] as $drive) {
            if ($result['top_speed'] < $drive['top_speed']) {
                $result['top_speed'] = $drive['top_speed'];
            }
            $avg_speed_sum += $drive['avg_speed'];
            $result['fuel_consumption'] += $drive['fuel_consumption'];
            $result['fuel_cost'] += $drive['fuel_cost'];
            $result['drives_duration_time'] += $drive['duration_seconds'];
            $result['engine_work_time'] += $drive['engine_work'];
            
            // Sum route length from drives with full precision
            $result['route_length'] += $drive['length'];
        }

        if (count($result['drives']) > 0) {
            $result['avg_speed'] = floor($avg_speed_sum / count($result['drives']));
        }

        foreach ($result['stops'] as $stop) {
            $result['fuel_consumption'] += $stop['fuel_consumption'];
            $result['fuel_cost'] += $stop['fuel_cost'];
            $result['stops_duration_time'] += $stop['duration_seconds'];
            $result['engine_idle_time'] += $stop['engine_idle'];
        }

        $result['engine_work_time'] += $result['engine_idle_time'];
        
        return $result;
    }

    private function getRouteRaw($imei, $accuracy, $dtf, $dtt, $userSettings)
    {
        $tableName = "gs_object_data_" . $imei;
        if (!Schema::hasTable($tableName)) {
            return [];
        }

        $ignore_after_speed = DB::table('gs_objects')->where('imei', (string)$imei)->value('ignore_after_speed');

        $query = DB::table($tableName)
            ->select('dt_tracker', 'lat', 'lng', 'altitude', 'angle', 'speed', 'params')
            ->distinct()
            ->whereBetween('dt_tracker', [$dtf, $dtt])
            ->orderBy('dt_tracker', 'asc');

        if ($ignore_after_speed > 0) {
            $query->where('speed', '<=', (float)$ignore_after_speed);
        }

        $rows = $query->get();
        $route = [];
        
        foreach ($rows as $row) {
            $params = json_decode($row->params, true) ?: [];
            
            if (isset($params['io201']) && (int)$params['io201'] === 65532) {
                continue;
            }

            // Accuracy filters
            if (($accuracy['use_gpslev'] ?? false)) {
                $gpslev = $params['gpslev'] ?? 0;
                if ($gpslev < ($accuracy['min_gpslev'] ?? 0)) continue;
            }
            if (($accuracy['use_hdop'] ?? false)) {
                $hdop = $params['hdop'] ?? 0;
                if ($hdop > ($accuracy['max_hdop'] ?? 100)) continue;
            }

            if ($row->lat != 0 && $row->lng != 0) {
                // Apply Timezone conversion exactly like convUserTimezone
                $dt = $row->dt_tracker;
                if ($userSettings) {
                    $dt = $this->convUserTimezone($dt, $userSettings);
                }

                $route[] = [
                    $dt,
                    (float) $row->lat,
                    (float) $row->lng,
                    (float) $row->altitude,
                    (float) $row->angle,
                    (float) $row->speed,
                    $params
                ];
            }
        }

        return $route;
    }

    private function convUserTimezone($dt, $userSettings)
    {
        $tz = $userSettings['timezone'] ?? '+ 0 hour';
        $dst = $userSettings['dst'] ?? 'false';
        
        // Match the legacy strtotime logic
        $time = strtotime($dt . " " . $tz);
        
        if ($dst == 'true') {
            // Simplified DST check for now, matching legacy logic
            // In a real system we should use proper PHP DateTimeZone
            $dt_formatted = date('m-d H:i:s', $time);
            $start = ($userSettings['dst_start'] ?? '') . ':00';
            $end = ($userSettings['dst_end'] ?? '') . ':00';
            
            // Legacy isDateInRange logic for DST
            if ($start && $end && $dt_formatted >= $start && $dt_formatted <= $end) {
                $time = strtotime("+1 hour", $time);
            }
        }
        
        return date("Y-m-d H:i:s", $time);
    }

    private function getObjectAccuracy($imei)
    {
        $default = [
            'stops' => 'gps',
            'route_length' => 'gps',
            'min_moving_speed' => 6,
            'min_idle_speed' => 3,
            'min_diff_points' => 0.0005,
            'use_gpslev' => false,
            'min_gpslev' => 5,
            'use_hdop' => false,
            'max_hdop' => 3,
            'ign_fuel_cons_stops' => false,
            'min_fuel_speed' => 10,
            'min_ff' => 10,
            'min_ft' => 10,
            'type' => ''
        ];

        $val = DB::table('gs_objects')->where('imei', (string)$imei)->value('accuracy');
        if (empty($val)) return $default;

        $decoded = json_decode($val, true);
        return array_merge($default, $decoded ?: []);
    }

    private function removeRouteJunkPoints($route, $accuracy)
    {
        if (count($route) < 1) return [];

        $temp = [];
        $min_moving_speed = $accuracy['min_moving_speed'];
        $min_diff_points = $accuracy['min_diff_points'];

        // Exact legacy filter drifting logic
        for ($i = 0; $i < count($route) - 1; $i++) {
            $lat1 = $route[$i][1];
            $lng1 = $route[$i][2];
            $lat2 = $route[$i + 1][1];
            $lng2 = $route[$i + 1][2];
            $speed = $route[$i][5];

            $lat_diff = abs($lat1 - $lat2);
            $lng_diff = abs($lng1 - $lng2);

            // Legacy precedence: (A || B || (C && D))
            if (($i == 0) || ($speed > $min_moving_speed) || (($lat_diff > $min_diff_points) && ($lng_diff > $min_diff_points))) {
                $lat_temp = $lat2;
                $lng_temp = $lng2;
                $temp[] = $route[$i];
            } else {
                if (isset($lat_temp)) {
                    $route[$i][1] = $lat_temp;
                    $route[$i][2] = $lng_temp;
                }
                $temp[] = $route[$i];
            }
        }
        $temp[] = $route[count($route) - 1]; // add last point

        return $temp;
    }

    private function getRouteStopsGPS($route, $accuracy, $fcr, $fuelSensors, $fuelConsSensors, $minStopDurationMinutes, $accParam, $accuracyType)
    {
        $stops = [];
        $stopped = 0;
        $min_moving_speed = $accuracy['min_moving_speed'];
        $id_start = 0;

        for ($i = 0; $i < count($route); $i++) {
            $speed = $route[$i][5];

            if ($speed <= $min_moving_speed && $i < count($route) - 1) {
                if ($stopped == 0) {
                    $id_start = $i;
                    $stopped = 1;
                }
            } else {
                if ($stopped == 1) {
                    $id_end = $i;
                    $dt_start = $route[$id_start][0];
                    $dt_end = $route[$id_end][0];
                    $duration_seconds = strtotime($dt_end) - strtotime($dt_start);

                    if ($duration_seconds >= ($minStopDurationMinutes * 60)) {
                        $fuel = $this->getRouteFuelConsumption($route, $id_start, $id_end, $fcr, $fuelSensors, $fuelConsSensors);
                        $stops[] = [
                            'idx_start' => $id_start,
                            'idx_end' => $id_end,
                            'lat' => $route[$id_start][1],
                            'lng' => $route[$id_start][2],
                            'dt_start' => $dt_start,
                            'dt_end' => $dt_end,
                            'duration_seconds' => $duration_seconds,
                            'fuel_consumption' => $fuel,
                            'fuel_cost' => $this->getRouteFuelCost($fuel, $fcr),
                            'engine_idle' => $this->getRouteEngineHours($route, $id_start, $id_end, $accParam, $accuracyType),
                        ];
                    }
                    $stopped = 0;
                }
            }
        }
        return $stops;
    }

    private function getRouteStopsACC($route, $accuracy, $fcr, $fuel_sensors, $fuelcons_sensors, $min_stop_duration, $acc, $accuracyType)
    {
        $stops = [];
        $stopped = 0;
        $id_start = 0;

        for ($i = 0; $i < count($route); $i++) {
            $params = $route[$i][6];
            $acc_val = $params[$acc] ?? '1';

            if ($acc_val == '0' && $i < count($route) - 1) {
                if ($stopped == 0) {
                    $id_start = $i;
                    $stopped = 1;
                }
            } else {
                if ($stopped == 1) {
                    $id_end = $i;
                    $dt_start = $route[$id_start][0];
                    $dt_end = $route[$id_end][0];
                    $duration_seconds = strtotime($dt_end) - strtotime($dt_start);

                    if ($duration_seconds >= ($min_stop_duration * 60)) {
                        $fuel = $this->getRouteFuelConsumption($route, $id_start, $id_end, $fcr, $fuel_sensors, $fuelcons_sensors);
                        $stops[] = [
                            'idx_start' => $id_start,
                            'idx_end' => $id_end,
                            'lat' => $route[$id_start][1],
                            'lng' => $route[$id_start][2],
                            'dt_start' => $dt_start,
                            'dt_end' => $dt_end,
                            'duration_seconds' => $duration_seconds,
                            'fuel_consumption' => $fuel,
                            'fuel_cost' => $this->getRouteFuelCost($fuel, $fcr),
                            'engine_idle' => $this->getRouteEngineHours($route, $id_start, $id_end, $acc, $accuracyType),
                        ];
                    }
                    $stopped = 0;
                }
            }
        }
        return $stops;
    }

    private function getRouteStopsGPSACC($route, $accuracy, $fcr, $fuel_sensors, $fuelcons_sensors, $min_stop_duration, $acc, $accuracyType)
    {
        $stops = [];
        $stopped = 0;
        $min_moving_speed = $accuracy['min_moving_speed'];
        $id_start = 0;

        for ($i = 0; $i < count($route); $i++) {
            $speed = $route[$i][5];
            $params = $route[$i][6];
            $acc_val = $params[$acc] ?? '1';

            if (($speed <= $min_moving_speed || $acc_val == '0') && $i < count($route) - 1) {
                if ($stopped == 0) {
                    $id_start = $i;
                    $stopped = 1;
                }
            } else {
                if ($stopped == 1) {
                    $id_end = $i;
                    $dt_start = $route[$id_start][0];
                    $dt_end = $route[$id_end][0];
                    $duration_seconds = strtotime($dt_end) - strtotime($dt_start);

                    if ($duration_seconds >= ($min_stop_duration * 60)) {
                        $fuel = $this->getRouteFuelConsumption($route, $id_start, $id_end, $fcr, $fuel_sensors, $fuelcons_sensors);
                        $stops[] = [
                            'idx_start' => $id_start,
                            'idx_end' => $id_end,
                            'lat' => $route[$id_start][1],
                            'lng' => $route[$id_start][2],
                            'dt_start' => $dt_start,
                            'dt_end' => $dt_end,
                            'duration_seconds' => $duration_seconds,
                            'fuel_consumption' => $fuel,
                            'fuel_cost' => $this->getRouteFuelCost($fuel, $fcr),
                            'engine_idle' => $this->getRouteEngineHours($route, $id_start, $id_end, $acc, $accuracyType),
                        ];
                    }
                    $stopped = 0;
                }
            }
        }
        return $stops;
    }

    private function getRouteDrives($route, $accuracy, $stops, $fcr, $fuel_sensors, $fuelcons_sensors, $acc, $odo_sensor, $accuracyType)
    {
        $drives = [];
        $last_end = 0;
        $prev_stop_start = 0;

        foreach ($stops as $stop) {
            if ($stop['idx_start'] > $last_end) {
                // Legacy pattern: distance calculated from start of prev stop
                $drives[] = $this->createDriveData($route, $prev_stop_start, $last_end, $stop['idx_start'], $accuracy, $fcr, $fuel_sensors, $fuelcons_sensors, $acc, $odo_sensor, $accuracyType);
            }
            $prev_stop_start = $stop['idx_start'];
            $last_end = $stop['idx_end'];
        }

        if ($last_end < count($route) - 1) {
            $drives[] = $this->createDriveData($route, $prev_stop_start, $last_end, count($route) - 1, $accuracy, $fcr, $fuel_sensors, $fuelcons_sensors, $acc, $odo_sensor, $accuracyType);
        }

        return $drives;
    }

    private function createDriveData($route, $id_start_s, $id_start, $id_end, $accuracy, $fcr, $fuel_sensors, $fuelcons_sensors, $acc, $odo_sensor, $accuracyType)
    {
        $dt_start = $route[$id_start][0];
        $dt_end = $route[$id_end][0];
        $duration_seconds = strtotime($dt_end) - strtotime($dt_start);

        // Distance from start of stop (id_start_s) to beginning of next stop (id_end)
        $length = $this->getRouteLength($route, $id_start_s, $id_end, $accuracy, $odo_sensor);
        $top_speed = $this->getRouteTopSpeed($route, $id_start, $id_end);
        
        // Exact legacy average speed calculation
        $avg_speed = $duration_seconds > 0 ? floor($length / ($duration_seconds / 3600)) : 0;
        if ($avg_speed > $top_speed) $avg_speed = $top_speed;

        $fuel = $this->getRouteFuelConsumption($route, $id_start, $id_end, $fcr, $fuel_sensors, $fuelcons_sensors);

        return [
            'status' => 'drive',
            'idx_start' => $id_start,
            'idx_end' => $id_end,
            'start' => $dt_start,
            'end' => $dt_end,
            'duration' => $this->formatSeconds($duration_seconds),
            'duration_seconds' => $duration_seconds,
            'length' => (float)$length,
            'top_speed' => round($top_speed, 2),
            'avg_speed' => round($avg_speed, 2),
            'fuel_consumption' => round($fuel, 2),
            'fuel_cost' => $this->getRouteFuelCost($fuel, $fcr),
            'engine_work' => $this->getRouteEngineHours($route, $id_start, $id_end, $acc, $accuracyType),
            'engine_idle' => 0
        ];
    }

    private function getRouteLength($route, $id_start, $id_end, $accuracy, $odo_sensor)
    {
        $length = 0;
        $source = $accuracy['route_length'] ?? 'gps';

        if ($source == 'odo' && $odo_sensor) {
            $odo1 = $this->getParamValue($route[$id_start][6], $odo_sensor->param);
            $odo2 = $this->getParamValue($route[$id_end][6], $odo_sensor->param);
            if ($odo1 > 0 && $odo2 > 0) return abs($odo2 - $odo1);
        }

        // GPS Length
        for ($i = $id_start; $i < $id_end; $i++) {
            $length += $this->calculateEarthDistance($route[$i][1], $route[$i][2], $route[$i+1][1], $route[$i+1][2]);
        }
        return $length;
    }

    private function getRouteTopSpeed($route, $id_start, $id_end)
    {
        $top = 0;
        for ($i = $id_start; $i <= $id_end; $i++) {
            if ($route[$i][5] > $top) $top = $route[$i][5];
        }
        return $top;
    }

    private function getRouteFuelConsumption($route, $id_start, $id_end, $fcr, $fuel_sensors, $fuelcons_sensors)
    {
        if (empty($fcr)) return 0;

        $source = $fcr['source'] ?? 'rates';
        $summer = (float)($fcr['summer'] ?? 0);
        $winter = (float)($fcr['winter'] ?? 0);
        $fuel = 0;

        if ($source == 'rates') {
            if ($summer > 0 || $winter > 0) {
                $winter_start = $fcr['winter_start'] ?? '12-01';
                $winter_end = $fcr['winter_end'] ?? '03-01';

                for ($i = $id_start; $i < $id_end; $i++) {
                    $dist = $this->calculateEarthDistance($route[$i][1], $route[$i][2], $route[$i+1][1], $route[$i+1][2]);
                    
                    $f_date = strtotime($route[$i][0]);
                    $f_date1 = strtotime(date("Y") . '-' . $winter_start);
                    $f_date2 = strtotime(date("Y") . '-' . $winter_end);
                    if ($f_date1 >= $f_date2) $f_date2 = strtotime((date("Y") + 1) . '-' . $winter_end);

                    $rate = ($f_date >= $f_date1 && $f_date <= $f_date2) ? $winter : $summer;
                    if ($rate > 0) {
                        if ($fcr['measurement'] == 'mpg') {
                            $fuel += ($dist / 1.60934) / $rate;
                        } else {
                            $fuel += ($dist / 100) * $rate;
                        }
                    }
                }
            }
        } else if ($source == 'fuel' && count($fuel_sensors) > 0) {
            foreach ($fuel_sensors as $sensor) {
                $v1 = $this->getSensorValue($route[$id_start][6], $sensor);
                $v2 = $this->getSensorValue($route[$id_end][6], $sensor);
                $diff = $v1 - $v2; 
                if ($diff > 0) $fuel += $diff;
            }
        } 
        return abs($fuel);
    }

    private function getRouteFuelCost($fuel, $fcr)
    {
        return $fuel * (float)($fcr['cost'] ?? 0);
    }

    private function getRouteEngineHours($route, $id_start, $id_end, $acc, $type = '')
    {
        $engine_hours = 0;
        if (count($route) <= $id_end) $id_end = count($route) - 1;

        if ($type == 'generator') {
            $start_time = null;
            for ($i = $id_start; $i <= $id_end; $i++) {
                $is_on = ($route[$i][6]['acc'] ?? '1') == '1';
                if ($is_on && $start_time === null) $start_time = strtotime($route[$i][0]);
                else if (!$is_on && $start_time !== null) {
                    $engine_hours += (strtotime($route[$i][0]) - $start_time);
                    $start_time = null;
                }
            }
            if ($start_time !== null) $engine_hours += (strtotime($route[$id_end][0]) - $start_time);
        } else {
            for ($i = $id_start; $i < $id_end; $i++) {
                if (($route[$i][6][$acc] ?? '0') == '1' && ($route[$i+1][6][$acc] ?? '0') == '1') {
                    $engine_hours += strtotime($route[$i+1][0]) - strtotime($route[$i][0]);
                }
            }
        }
        return $engine_hours;
    }

    public function getOverspeeds($route, $speedLimit)
    {
        $overspeeds = [];
        $isOverspeed = false;
        $idStart = 0;

        for ($i = 0; $i < count($route); $i++) {
            if ($route[$i][5] > $speedLimit) {
                if (!$isOverspeed) {
                    $idStart = $i;
                    $isOverspeed = true;
                }
            } else {
                if ($isOverspeed) {
                    $overspeeds[] = ['start' => $idStart, 'end' => $i - 1];
                    $isOverspeed = false;
                }
            }
        }
        if ($isOverspeed) $overspeeds[] = ['start' => $idStart, 'end' => count($route) - 1];
        
        return $overspeeds;
    }

    private function calculateEarthDistance($lat1, $lon1, $lat2, $lon2)
    {
        if ($lat1 == $lat2 && $lon1 == $lon2) return 0;
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $km = $dist * 60 * 1.1515 * 1.609344;
        return (float) sprintf("%01.6f", $km);
    }

    private function getParamValue($params, $param)
    {
        return $params[$param] ?? 0;
    }

    private function getSensorValue($params, $sensor)
    {
        $param = is_object($sensor) ? ($sensor->param ?? '') : ($sensor['param'] ?? '');
        $val = $params[$param] ?? 0;
        return (float) $val;
    }

    private function getSensorParamByType($imei, $type)
    {
        $sensor = DB::table('gs_object_sensors')
            ->where('imei', (string)$imei)
            ->where('type', $type)
            ->first(['param']);
        return $sensor ? ($sensor->param ?? null) : null;
    }

    private function getSensorByType($imei, $type)
    {
        return DB::table('gs_object_sensors')
            ->where('imei', (string)$imei)
            ->where('type', $type)
            ->first();
    }

    private function getSensorsByType($imei, $type)
    {
        return DB::table('gs_object_sensors')
            ->where('imei', (string)$imei)
            ->where('type', $type)
            ->get();
    }

    private function getObjectFCR($imei)
    {
        $default = [
            'source' => 'rates',
            'measurement' => 'l100km',
            'cost' => 0,
            'summer' => 0,
            'winter' => 0,
            'winter_start' => '12-01',
            'winter_end' => '03-01'
        ];

        $val = DB::table('gs_objects')->where('imei', (string)$imei)->value('fcr');
        if (empty($val)) return $default;

        $decoded = json_decode($val, true);
        return array_merge($default, $decoded ?: []);
    }

    private function formatSeconds($seconds)
    {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        return sprintf("%02d:%02d:%02d", $h, $m, $s);
    }
}
