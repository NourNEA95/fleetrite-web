<?php

namespace App\Services;

use App\Models\Report;
use App\Models\GeneratedReport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class ReportService
{
    protected $trackingService;

    public function __construct(TrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    public function generate($params, $userId)
    {
        $type = $params['type'] ?? 'general';
        $imeis = is_array($params['imei'] ?? []) ? $params['imei'] : explode(',', $params['imei'] ?? '');
        $imeis = array_unique(array_filter(array_map('trim', $imeis)));
        $imeiList = implode(',', $imeis);
        
        $userSettings = $params['user_settings'] ?? null;

        // Splitting dates and times for the API format
        $dtf_parts = explode(' ', $params['from']);
        $dtt_parts = explode(' ', $params['to']);
        
        $date_from = $dtf_parts[0];
        $time_from = explode(':', $dtf_parts[1] ?? '00:00:00');
        
        $date_to = $dtt_parts[0];
        $time_to = explode(':', $dtt_parts[1] ?? '00:00:00');

        // Preparing data list
        if (isset($params['data_items']) && !empty($params['data_items'])) {
            $data_items = is_array($params['data_items']) ? $params['data_items'] : explode(',', $params['data_items']);
        } else {
            $data_items = [
                'route_start', 'route_end', 'route_length', 'move_duration', 'stop_duration', 
                'stop_count', 'top_speed', 'avg_speed', 'overspeed_count', 'fuel_consumption', 
                'avg_fuel_consumption', 'fuel_cost', 'engine_work', 'engine_idle', 'odometer', 
                'engine_hours', 'driver', 'trailer'
            ];
        }

        $apiType = 'general_save';
        if ($type === 'general_merged') {
            $apiType = 'general_merged_save';
        } else if ($type === 'general_accuracy') {
            $apiType = 'general_accuracy_save';
        } else if ($type === 'route_data_sensors') {
            $apiType = 'route_data_sensors_save';
        } else if ($type === 'drives_stops' || $type === 'drives_stops_sensors' || $type === 'drives_stops_logic') {
            $apiType = 'drives_stops_save';
        } else if ($type === 'kml') {
            $apiType = 'kml_save';
        }

        $postData = [
            'form_user_id' => $userId,
            'user_id' => $userId,
            'dialog_report_date_from' => $date_from,
            'dialog_report_hour_from' => $time_from[0],
            'dialog_report_minute_from' => $time_from[1],
            'dialog_report_date_to' => $date_to,
            'dialog_report_hour_to' => $time_to[0],
            'dialog_report_minute_to' => $time_to[1],
            'dialog_report_format' => 'html',
            'dialog_report_ignore_empty_reports' => 'false',
            'dialog_report_show_coordinates' => ($params['show_coordinates'] ?? false) ? 'true' : 'false',
            'dialog_report_show_addresses' => ($params['show_addresses'] ?? false) ? 'true' : 'false',
            'dialog_report_markers_addresses' => ($params['markers_addresses'] ?? false) ? 'true' : 'false',
            'dialog_report_zones_addresses' => ($params['zones_addresses'] ?? false) ? 'true' : 'false',
            'dialog_report_stop_duration' => $params['stop_duration'] ?? 5,
            'dialog_report_speed_limit' => $params['speed_limit'] ?? '',
            'dialog_report_filter' => 2,
            'dialog_report_type' => $apiType,
            'dialog_report_object_list' => $imeiList,
            'dialog_report_driver_list' => is_array($params['driver_ids'] ?? '') ? implode(',', $params['driver_ids']) : ($params['driver_ids'] ?? ''),
            'dialog_report_sensor_list' => is_array($params['sensor_names'] ?? '') ? implode(',', $params['sensor_names']) : ($params['sensor_names'] ?? ''),
            'dialog_report_marker_list' => is_array($params['marker_ids'] ?? '') ? implode(',', $params['marker_ids']) : ($params['marker_ids'] ?? ''),
            'dialog_report_zone_list' => is_array($params['zone_ids'] ?? '') ? implode(',', $params['zone_ids']) : ($params['zone_ids'] ?? ''),
            'dialog_report_data_item_list' => ($type === 'route_data_sensors') ? 'time,position,speed,altitude,angle' : implode(',', $data_items),
            'dialog_report_name' => $params['name'] ?? '',
            'dialog_report_name2' => $params['name2'] ?? '',
            'dialog_report_name3' => $params['name3'] ?? '',
            'dialog_report_other_dn_starts_hour' => $params['other_dn_starts_hour'] ?? '',
            'dialog_report_other_dn_starts_minute' => $params['other_dn_starts_minute'] ?? '',
            'dialog_report_other_dn_ends_hour' => $params['other_dn_ends_hour'] ?? '',
            'dialog_report_other_dn_ends_minute' => $params['other_dn_ends_minute'] ?? '',
            'dialog_report_other_rag_low_score' => $params['other_rag_low_score'] ?? '',
            'dialog_report_other_rag_high_score' => $params['other_rag_high_score'] ?? '',
            'dialog_report_schedule_period_daily' => ($params['schedule_daily'] ?? false) ? 'on' : '',
            'dialog_report_schedule_period_weekly' => ($params['schedule_weekly'] ?? false) ? 'on' : '',
            'dialog_report_schedule_period_monthly' => ($params['schedule_monthly'] ?? false) ? 'on' : '',
            'dialog_report_schedule_email_address' => $params['schedule_email_address'] ?? '',
            'pass_key' => 'AAA21A609BFD46C1437E01867D22913B'
        ];

        $apiUrl = (request() ? request()->getSchemeAndHttpHost() : config('app.url')) . '/fleetrite_nv_latest_version/func/process_api.php';
        
        $isGeneralKeyBased = in_array($type, ['general', 'general_information', 'general_merged', 'general_accuracy'], true);

        // Parallel save for general reports: split IMEIs into multiple process_api.php calls.
        if ($isGeneralKeyBased && count($imeis) > 1) {
            $imeiBatchSize = (int) ($params['imei_batch_size'] ?? 25);
            if ($imeiBatchSize < 1) $imeiBatchSize = 1;

            $maxParallel = (int) ($params['parallel_max'] ?? 10);
            if ($maxParallel < 1) $maxParallel = 1;

            $imeiChunks = array_chunk($imeis, $imeiBatchSize);

            $keys = [];
            $mergedResults = [];
            $totalSum = 0;
            $savedOkSum = 0;

            for ($i = 0; $i < count($imeiChunks); $i += $maxParallel) {
                $batchChunks = array_slice($imeiChunks, $i, $maxParallel);

                $batchPostDatas = [];
                foreach ($batchChunks as $chunk) {
                    $batchPost = $postData;
                    $batchPost['dialog_report_object_list'] = implode(',', $chunk);
                    $batchPostDatas[] = $batchPost;
                }

                $responses = Http::pool(function ($pool) use ($batchPostDatas, $apiUrl) {
                    $requests = [];
                    foreach ($batchPostDatas as $pd) {
                        $requests[] = $pool->asForm()->timeout(300)->post($apiUrl, $pd);
                    }
                    return $requests;
                });

                foreach ($responses as $response) {
                    if (!$response->successful()) {
                        return ['status' => 'error', 'message' => 'API Error'];
                    }

                    $jsonResponse = $response->json();
                    $key = $jsonResponse['key'] ?? null;
                    if ($key) $keys[] = $key;

                    $totalSum += (int) ($jsonResponse['total'] ?? 0);
                    $savedOkSum += (int) ($jsonResponse['saved_ok'] ?? 0);
                    if (isset($jsonResponse['results']) && is_array($jsonResponse['results'])) {
                        $mergedResults = array_merge($mergedResults, $jsonResponse['results']);
                    }
                }
            }

            $keys = array_values(array_unique($keys));

            return [
                'status' => 'success',
                'key' => $keys[0] ?? '',
                'keys' => $keys,
                'ok' => true,
                'total' => $totalSum ?: count($imeis),
                'saved_ok' => $savedOkSum ?: count($imeis),
                'results' => $mergedResults,
                        'data' => null, // Frontend will load data via POST /reports/general-info/data (keys in body)
            ];
        }

        // Single process_api.php call
        $response = Http::asForm()->timeout(300)->post($apiUrl, $postData);

        if ($response->successful()) {
            $jsonResponse = $response->json();
            $key = $jsonResponse['key'] ?? null;

            if ($key) {
                if ($isGeneralKeyBased) {
                    return [
                        'status' => 'success',
                        'key' => $key,
                        'keys' => [$key],
                        'ok' => $jsonResponse['ok'] ?? true,
                        'total' => $jsonResponse['total'] ?? 1,
                        'saved_ok' => $jsonResponse['saved_ok'] ?? 1,
                        'results' => $jsonResponse['results'] ?? [],
                        'data' => null,
                    ];
                }

                $results = $this->fetch($type, $key);
                return [
                    'status' => 'success',
                    'key' => $key,
                    'results' => $results,
                    'debug' => $jsonResponse
                ];
            }
        }

        Log::error('ReportService API Error', [
            'status' => $response->status(),
            'body' => $response->body(),
            'postData' => $postData
        ]);
        return ['status' => 'error', 'message' => 'API Error: Missing key or invalid response'];
    }

    private function fetchRouteDataSensors($key)
    {
        // 1. Fetch labels from first row
        $headerRow = DB::table('route_data_sensors_repotrs2')
            ->where('table_key', $key)
            ->where('type', 0)
            ->first(['sensor_lables']);
        
        $labels = [];
        if ($headerRow) {
            $labels = json_decode($headerRow->sensor_lables, true);
        }
        if (!is_array($labels)) $labels = [];

        // Identify position index (case-insensitive)
        $posIdx = null;
        // Search for POSITION in various languages or default
        $possiblePositions = ['POSITION', 'الموقع']; 
        
        foreach ($labels as $idx => $label) {
            $uLabel = mb_strtoupper(trim((string)$label), 'UTF-8');
            foreach ($possiblePositions as $p) {
                if ($uLabel === mb_strtoupper($p, 'UTF-8')) {
                    $posIdx = $idx;
                    break 2;
                }
            }
        }

        // 2. Fetch data rows
        $rows = DB::table('route_data_sensors_repotrs2')
            ->where('table_key', $key)
            ->orderBy('id', 'asc')
            ->get(['position', 'sensor_values']);

        $processed = [];
        foreach ($rows as $row) {
            $vals = json_decode($row->sensor_values, true);
            if (!is_array($vals)) $vals = [$row->sensor_values];

            // Address column shift: The legacy API adds one 'POSITION' label but TWO values (Lat, Lng)
            // when coordinates are enabled. If labels count is less than values count, merge the extra coordinate.
            if ($posIdx !== null && count($vals) > count($labels)) {
                // Merge Lat and Lng at posIdx
                $lat = $vals[$posIdx] ?? '0';
                $lng = $vals[$posIdx + 1] ?? '0';
                $vals[$posIdx] = $lat . ', ' . $lng;
                
                // Remove the extra value (Lng) to fix the shift
                array_splice($vals, $posIdx + 1, 1);
            }

            // Overwrite with address if available in DB position column
            if ($posIdx !== null && isset($row->position) && !empty($row->position)) {
                $vals[$posIdx] = $row->position;
            }

            // Create flat indexed object for frontend
            $item = [
                'is_route_data' => true,
                'labels' => $labels,
                'values' => $vals
            ];
            $processed[] = $item;
        }

        return $processed;
    }

    private function addGlobalTotal($processedRows)
    {
        $totals = [
            'route_length' => 0,
            'route_duration' => 0,
            'stop_duration' => 0,
            'stop_count' => 0,
            'top_speed' => 0,
            'overspeed_count' => 0,
            'fuel_consumption' => 0,
            'average_fuel' => 0,
            'fuel_cost' => 0,
            'engine_work' => 0,
            'engine_idle' => 0,
            'odometer' => 0,
            'engine_hours' => 0
        ];

        foreach ($processedRows as $row) {
            $totals['route_length'] += floatval($row['route_length'] ?? 0);
            $totals['route_duration'] += $this->parseDurationToSeconds($row['route_duration'] ?? '');
            $totals['stop_duration'] += $this->parseDurationToSeconds($row['stop_duration'] ?? '');
            $totals['stop_count'] += (int) ($row['stop_count'] ?? 0);
            $totals['top_speed'] += (int) ($row['top_speed'] ?? 0);
            $totals['overspeed_count'] += (int) ($row['overspeed_count'] ?? 0);
            $totals['fuel_consumption'] += floatval($row['fuel_consumption'] ?? 0);
            $totals['average_fuel'] += floatval($row['average_fuel'] ?? 0);
            $totals['fuel_cost'] += floatval($row['fuel_cost'] ?? 0);
            $totals['engine_work'] += $this->parseDurationToSeconds($row['engine_work'] ?? '');
            $totals['engine_idle'] += $this->parseDurationToSeconds($row['engine_idle'] ?? '');
            $totals['odometer'] += floatval($row['odometer'] ?? 0);
            $totals['engine_hours'] += $this->parseDurationToSeconds($row['engine_hours'] ?? '');
        }

        $totalRow = [
            'object' => 'Total:',
            'group' => '-',
            'route_start' => '-',
            'route_end' => '-',
            'route_length' => round($totals['route_length'], 2) . ' km',
            'route_duration' => $this->formatSeconds($totals['route_duration']),
            'stop_duration' => $this->formatSeconds($totals['stop_duration']),
            'stop_count' => $totals['stop_count'],
            'top_speed' => $totals['top_speed'],
            'average_speed' => '-',
            'overspeed_count' => $totals['overspeed_count'],
            'fuel_consumption' => round($totals['fuel_consumption'], 2) . ' Liter',
            'average_fuel' => round($totals['average_fuel'], 2) . ' Liter',
            'fuel_cost' => round($totals['fuel_cost'], 2) . " EUR",
            'engine_work' => $this->formatSeconds($totals['engine_work']),
            'engine_idle' => $this->formatSeconds($totals['engine_idle']),
            'odometer' => round($totals['odometer'], 2) . ' km',
            'engine_hours' => $this->formatSeconds($totals['engine_hours'], false),
            'driver' => '-',
            'flag_all_day' => '-',
            'is_total' => true
        ];

        return $totalRow;
    }

    private function convUserUTCTimezone($dt, $userSettings)
    {
        if (!$userSettings) return $dt;

        $tz = $userSettings['timezone'] ?? '+ 0 hour';
        $dst = $userSettings['dst'] ?? 'false';

        // Invert timezone shift for DB query
        if (substr($tz, 0, 1) == "+") {
            $tz_diff = str_replace("+", "-", $tz);
        } else {
            $tz_diff = str_replace("-", "+", $tz);
        }

        $time = strtotime($dt . " " . $tz_diff);

        // DST handling matching legacy
        if ($dst == 'true') {
            $dt_ = date('m-d H:i:s', $time);
            $start = ($userSettings['dst_start'] ?? '') . ':00';
            $end = ($userSettings['dst_end'] ?? '') . ':00';
            if ($start && $end && $dt_ >= $start && $dt_ <= $end) {
                $time = strtotime("-1 hour", $time);
            }
        }

        return date("Y-m-d H:i:s", $time);
    }

    /**
     * Paginated fetch for general information report from general_information_reports2_api (process_api.php key(s)).
     * Returns 1000 rows per page by default and a global totals row computed from the full set.
     */
    public function fetchGeneralInfoPaginated($keys, int $page = 1, int $perPage = 1000, array $dataItems = [])
    {
        if (is_string($keys)) {
            $keys = array_filter(array_map('trim', explode(',', $keys)));
        }
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        $query = DB::table('general_information_reports2_api')
            ->whereIn('table_key', $keys)
            ->where('type', 0)
            ->orderBy('id');

        $totalCount = $query->count();
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $rows = $paginator->items();

        $processedRows = $this->cleanRowsFromApiTable($rows);

        // Totals row: compute from full set (same as legacy)
        $allRows = DB::table('general_information_reports2_api')
            ->whereIn('table_key', $keys)
            ->where('type', 0)
            ->get();
        $totalsRow = $this->computeGeneralInfoTotals($allRows);

        return [
            'data' => $processedRows,
            'total' => $totalCount,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'totals_row' => $totalsRow,
        ];
    }

    private function cleanRowsFromApiTable($rows)
    {
        $processedRows = [];
        foreach ($rows as $row) {
            $rowObj = (array) $row;
            $rowObj['route_length'] = trim(str_replace(' km', '', $rowObj['route_length'] ?? '0'));
            $rowObj['fuel_consumption'] = trim(str_replace(' Liter', '', $rowObj['fuel_consumption'] ?? '0'));
            $rowObj['average_fuel'] = trim(str_replace(' Liter', '', $rowObj['average_fuel'] ?? '0'));
            $rowObj['odometer'] = trim(str_replace(' km', '', $rowObj['odometer'] ?? '0'));
            $rowObj['group'] = $rowObj['status'] ?? '';
            $rowObj['trailer'] = $rowObj['flag_all_day'] ?? '-';
            $rowObj['speed_limit'] = $rowObj['average_speed'] ?? ''; // legacy: average_speed column stores speed limit
            $processedRows[] = $rowObj;
        }
        return $processedRows;
    }

    private function computeGeneralInfoTotals($rows)
    {
        $totals = [
            'route_length' => 0,
            'route_duration' => 0,
            'stop_duration' => 0,
            'stop_count' => 0,
            'top_speed' => 0,
            'overspeed_count' => 0,
            'fuel_consumption' => 0,
            'average_fuel' => 0,
            'fuel_cost' => 0,
            'engine_work' => 0,
            'engine_idle' => 0,
            'odometer' => 0,
            'engine_hours' => 0,
        ];
        foreach ($rows as $row) {
            $r = (array) $row;
            $totals['route_length'] += floatval(str_replace(' km', '', $r['route_length'] ?? '0'));
            $totals['route_duration'] += $this->parseDurationToSeconds($r['route_duration'] ?? '');
            $totals['stop_duration'] += $this->parseDurationToSeconds($r['stop_duration'] ?? '');
            $totals['stop_count'] += (int) ($r['stop_count'] ?? 0);
            $totals['top_speed'] += (int) ($r['top_speed'] ?? 0);
            $totals['overspeed_count'] += (int) ($r['overspeed_count'] ?? 0);
            $totals['fuel_consumption'] += floatval(str_replace(' Liter', '', $r['fuel_consumption'] ?? '0'));
            $totals['average_fuel'] += floatval(str_replace(' Liter', '', $r['average_fuel'] ?? '0'));
            $totals['fuel_cost'] += floatval($r['fuel_cost'] ?? 0);
            $totals['engine_work'] += $this->parseDurationToSeconds($r['engine_work'] ?? '');
            $totals['engine_idle'] += $this->parseDurationToSeconds($r['engine_idle'] ?? '');
            $totals['odometer'] += floatval(str_replace(' km', '', $r['odometer'] ?? '0'));
            $totals['engine_hours'] += $this->parseDurationToSeconds($r['engine_hours'] ?? '');
        }
        return [
            'object' => 'Total:',
            'group' => '-',
            'route_start' => '-',
            'route_end' => '-',
            'route_length' => round($totals['route_length'], 2) . ' km',
            'route_duration' => $this->formatSeconds($totals['route_duration']),
            'stop_duration' => $this->formatSeconds($totals['stop_duration']),
            'stop_count' => $totals['stop_count'],
            'top_speed' => $totals['top_speed'],
            'average_speed' => '-',
            'overspeed_count' => $totals['overspeed_count'],
            'fuel_consumption' => round($totals['fuel_consumption'], 2) . ' liters',
            'average_fuel' => round($totals['average_fuel'], 2) . ' liters',
            'fuel_cost' => round($totals['fuel_cost'], 2) . ' EUR',
            'engine_work' => $this->formatSeconds($totals['engine_work']),
            'engine_idle' => $this->formatSeconds($totals['engine_idle']),
            'odometer' => round($totals['odometer'], 2) . ' km',
            'engine_hours' => $this->formatSeconds($totals['engine_hours'], false),
            'driver' => '-',
            'trailer' => '-',
            'flag_all_day' => '-',
            'is_total' => true,
        ];
    }

    public function fetch($type, $key)
    {
        switch ($type) {
            case 'general':
            case 'general_merged':
            case 'general_accuracy':
                $rows = DB::table('general_information_reports2')
                    ->where('table_key', $key)
                    ->get();
                
                return $this->cleanRows($rows);
            
            case 'drives_stops':
            case 'drives_stops_sensors':
            case 'drives_stops_logic':
                return DB::table('drives_stops_reports2')
                    ->where('table_key', $key)
                    ->orderBy('id', 'asc')
                    ->get();

            default:
                return [];
        }
    }

    private function cleanRows($rows)
    {
        $processedRows = [];

        foreach ($rows as $row) {
            $rowObj = (array) $row;
            
            // Fix "km km" and "Liter Liter" by stripping units from the values
            $rowObj['route_length'] = trim(str_replace(' km', '', $rowObj['route_length'] ?? '0'));
            $rowObj['fuel_consumption'] = trim(str_replace(' Liter', '', $rowObj['fuel_consumption'] ?? '0'));
            $rowObj['average_fuel'] = trim(str_replace(' Liter', '', $rowObj['average_fuel'] ?? '0'));
            $rowObj['odometer'] = trim(str_replace(' km', '', $rowObj['odometer'] ?? '0'));
            
            // Map status as group for UI
            $rowObj['group'] = $rowObj['status'];

            $processedRows[] = $rowObj;
        }

        return $processedRows;
    }

    private function parseDurationToSeconds($duration)
    {
        if (empty($duration) || $duration == '-') return 0;
        
        $seconds = 0;
        
        // Matches: 15 d 3 h 48 min 51 s or 02:30:15
        if (strpos($duration, ':') !== false) {
            $parts = explode(':', $duration);
            if (count($parts) == 3) {
                return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
            }
        }

        if (preg_match('/(\d+)\s*d/', $duration, $m)) $seconds += $m[1] * 86400;
        if (preg_match('/(\d+)\s*h/', $duration, $m)) $seconds += $m[1] * 3600;
        if (preg_match('/(\d+)\s*min/', $duration, $m)) $seconds += $m[1] * 60;
        if (preg_match('/(\d+)\s*s/', $duration, $m)) $seconds += $m[1];

        return $seconds;
    }

    private function saveGeneralReport($userId, $imei, $info, $key, $dtf, $dtt, $routeData)
    {
        // EXACT mappings from legacy reportsGenerateGeneralInfo_save:
        // average_speed column => stores speedLimit
        // flag_all_day column => stores trailer
        // status column => stores group

        // Preparation for database insertion
        $driver = $info['driver'];
        $trailer = $info['trailer'];
        $group = $info['group'];

        // Disable strict mode so MySQL handles missing fields with its defaults (0000-00-00...)
        // exactly like the legacy PHP scripts do.
        DB::statement("SET SESSION sql_mode=''");

        DB::table('general_information_reports2')->insert([
            'user_id' => $userId,
            'imei' => $imei,
            'table_key' => $key,
            'type' => 0,
            'object' => $info['object'],
            'route_start' => $info['route_start'],
            'route_end' => $info['route_end'],
            'route_length' => $info['route_length'],
            'route_duration' => $info['move_duration'],
            'stop_duration' => $info['stop_duration'],
            'stop_count' => $info['stop_count'],
            'top_speed' => $info['top_speed'],
            'average_speed' => $info['speed_limit'],
            'overspeed_count' => $info['overspeed_count'],
            'fuel_consumption' => $info['fuel_consumption'],
            'average_fuel' => $info['average_fuel'],
            'fuel_cost' => $info['fuel_cost'],
            'engine_work' => $info['engine_work'],
            'engine_idle' => $info['engine_idle'],
            'odometer' => $info['odometer'],
            'engine_hours' => $info['engine_hours'],
            'driver' => $driver,
            'dtf' => '0000-00-00 00:00:00', // Legacy saves 0000-00-00
            'dtt' => '0000-00-00 00:00:00', // Legacy saves 0000-00-00
            'flag_all_day' => $trailer ?: 'n/a',
            'status' => $group ?: ''
        ]);
    }

    private function saveDrivesStopsReport($userId, $imei, $info, $key)
    {
        foreach ($info['rows'] as $row) {
            DB::table('drives_stops_reports2')->insert([
                'user_id' => $userId,
                'imei' => $imei,
                'table_key' => $key,
                'type' => 0,
                'status' => $row['status'],
                'start' => $row['start'],
                'end' => $row['end'],
                'duration' => $row['duration'],
                'length' => (string) $row['length'],
                'top_speed' => (string) $row['top_speed'],
                'avg_speed' => (string) $row['avg_speed'],
                'fuel_consumption' => $row['fuel_consumption'],
                'fuel_cost' => '0',
                'engine_idle' => $row['engine_idle'],
                'engine_work' => '0',
                'address' => '-'
            ]);
        }
    }

    private function processGeneralReport($userId, $imei, $data, $speedLimit)
    {
        $gsObject = DB::table('gs_objects')->where('imei', (string)$imei)->first(['name', 'odometer', 'engine_hours', 'fcr']);
        $objectName = $gsObject ? ($gsObject->name ?? $imei) : $imei;
        
        $userObject = DB::table('gs_user_objects')->where('imei', (string)$imei)->where('user_id', $userId)->first(['group_id', 'driver_id', 'trailer_id']);
        $groupName = '-';
        if ($userObject && isset($userObject->group_id) && $userObject->group_id > 0) {
            $groupName = DB::table('gs_user_object_groups')->where('group_id', $userObject->group_id)->value('group_name') ?? '-';
        }

        $odometer = $gsObject->odometer ?? 0;
        $totalEngineHours = $gsObject->engine_hours ?? 0;

        $driverName = 'N/A';
        if ($userObject && isset($userObject->driver_id)) {
            if ($userObject->driver_id > 0) {
                $driverName = DB::table('gs_user_object_drivers')
                    ->where('user_id', $userId)
                    ->where('driver_id', $userObject->driver_id)
                    ->value('driver_name') ?: 'N/A';
            } elseif ($userObject->driver_id == 0) {
                $routeData = $data['route'] ?? [];
                $lastPoint = $routeData[count($routeData) - 1] ?? null;
                $params = $lastPoint[6] ?? [];
                $driverName = $this->getDriverFromSensorsDirect($imei, $userId, $params) ?? 'N/A';
                
                // If still N/A, try searching raw data for driverUniqueId (Legacy getObjectDriverFromSensor_new)
                if ($driverName === 'N/A') {
                    $driverName = $this->getDriverFromRawData($imei, $userId, $params['dt_tracker'] ?? null) ?? 'N/A';
                }
            }
        }

        $trailerName = 'n/a';
        if ($userObject && isset($userObject->trailer_id) && $userObject->trailer_id > 0) {
           $trailerName = DB::table('gs_user_object_trailers')->where('trailer_id', $userObject->trailer_id)->value('trailer_name') ?? 'n/a';
        }

        $overspeedCount = 0;
        if ($speedLimit > 0) {
            $overspeedEvents = $this->trackingService->getOverspeeds($data['route'], $speedLimit);
            $overspeedCount = count($overspeedEvents);
        }

        $routeLength = $data['route_length'];
        
        $route_start = (!empty($data['drives'])) ? $data['drives'][0]['start'] : '-';
        $route_end = (!empty($data['drives'])) ? $data['drives'][count($data['drives']) - 1]['end'] : '-';

        return [
            'object' => $objectName,
            'group' => $groupName,
            'imei' => $imei,
            'route_start' => $route_start,
            'route_end' => $route_end,
            'route_length' => round($routeLength, 2) . ' km',
            'move_duration' => $this->formatSeconds($data['drives_duration_time']),
            'stop_duration' => $this->formatSeconds($data['stops_duration_time']),
            'stop_count' => count($data['stops']),
            'top_speed' => $data['top_speed'],
            'avg_speed' => $data['avg_speed'] . ' km/h',
            'speed_limit' => $speedLimit,
            'overspeed_count' => $overspeedCount,
            'fuel_consumption' => round($data['fuel_consumption'], 2) . ' Liter',
            'engine_work' => $this->formatSeconds($data['engine_work_time']),
            'engine_idle' => $this->formatSeconds($data['engine_idle_time']),
            'odometer' => floor($odometer ?? 0) . ' km',
            'engine_hours' => $this->formatSeconds($totalEngineHours ?? 0, false),
            'average_fuel' => ($routeLength > 0) ? round(($data['fuel_consumption'] / $routeLength) * 100, 2) . ' Liter' : '0 Liter',
            'fuel_cost' => round($data['fuel_cost'], 2),
            'driver' => $driverName,
            'trailer' => $trailerName
        ];
    }

    private function processDrivesStopsReport($imei, $data)
    {
        $objectName = DB::table('gs_objects')->where('imei', (string)$imei)->value('name') ?? $imei;
        
        $rows = [];
        $items = array_merge($data['drives'], $data['stops']);
        usort($items, function($a, $b) {
            $t1 = isset($a['dt_start']) ? strtotime($a['dt_start']) : strtotime($a['start']);
            $t2 = isset($b['dt_start']) ? strtotime($b['dt_start']) : strtotime($b['start']);
            return $t1 <=> $t2;
        });

        foreach ($items as $item) {
            $isStop = isset($item['lat']); 
            
            $rows[] = [
                'status' => $item['status'] ?? ($isStop ? 'stop' : 'drive'),
                'start' => $item['dt_start'] ?? $item['start'],
                'end' => $item['dt_end'] ?? $item['end'],
                'duration' => $this->formatSeconds($item['duration_seconds']),
                'length' => $isStop ? '-' : ($item['length'] ?? 0),
                'top_speed' => $isStop ? '-' : ($item['top_speed'] ?? 0),
                'avg_speed' => $isStop ? '-' : ($item['avg_speed'] ?? 0),
                'fuel_consumption' => $item['fuel_consumption'] ?? 0,
                'engine_idle' => $item['engine_idle'] ?? 0
            ];
        }

        return [
            'object' => $objectName,
            'imei' => $imei,
            'rows' => $rows
        ];
    }

    private function formatSeconds($seconds, $includeDays = true)
    {
        if ($seconds < 0) $seconds = 0;
        
        $d = ($includeDays) ? floor($seconds / 86400) : 0;
        $h = ($includeDays) ? floor(($seconds % 86400) / 3600) : floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;

        $res = [];
        if ($d > 0) $res[] = "{$d} d";
        if ($h > 0 || $d > 0) $res[] = "{$h} h";
        if ($m > 0 || $h > 0 || $d > 0) $res[] = "{$m} min";
        $res[] = "{$s} s";

        return implode(' ', $res);
    }

    private function getDriverFromSensorsDirect($imei, $userId, $params)
    {
        // Check rfid, ibutton, or driverUniqueId
        $assignId = $params['da'] ?? $params['rfid'] ?? $params['ibutton'] ?? $params['driverUniqueId'] ?? null;
        
        if ($assignId && (string)$assignId != '0') {
            return DB::table('gs_user_object_drivers')
                ->where('user_id', $userId)
                ->where('driver_assign_id', (string)$assignId)
                ->value('driver_name');
        }

        return null;
    }

    private function getDriverFromRawData($imei, $userId, $time = null)
    {
        $tableName = "gs_object_data_" . $imei;
        if (!Schema::hasTable($tableName)) return null;

        $query = DB::table($tableName)
            ->whereNotNull('params')
            // Using a more robust LIKE pattern to catch both escaped and unescaped JSON variants
            ->where(function($q) {
                $q->where('params', 'like', '%"driverUniqueId":"%')
                  ->orWhere('params', 'like', '%\"driverUniqueId\":\"%');
            })
            ->orderBy('dt_tracker', 'desc');

        if ($time) {
            $query->where('dt_tracker', '<=', $time);
        }

        $row = $query->first(['params']);
        if ($row) {
            $params = json_decode($row->params, true);
            $assignId = $params['driverUniqueId'] ?? null;
            if ($assignId) {
                return DB::table('gs_user_object_drivers')
                    ->where('user_id', $userId)
                    ->where('driver_assign_id', (string)$assignId)
                    ->value('driver_name');
            }
        }
        return null;
    }

    private function getDriverFromSensors($imei, $userId)
    {
        $tableName = "gs_object_data_" . $imei;
        if (!Schema::hasTable($tableName)) return null;

        // Try to find the latest valid driver ID from sensors in the data
        // For now, look for params like 'da', 'iButton', 'rfid' etc in the last 100 points
        $rows = DB::table($tableName)
            ->select('params')
            ->orderBy('dt_tracker', 'desc')
            ->limit(100)
            ->get();

        foreach ($rows as $row) {
            $params = json_decode($row->params, true) ?: [];
            $assignId = $params['da'] ?? $params['rfid'] ?? $params['ibutton'] ?? null;
            
            if ($assignId && $assignId != '0') {
                $driverName = DB::table('gs_user_object_drivers')
                    ->where('user_id', $userId)
                    ->where('driver_assign_id', $assignId)
                    ->value('driver_name');
                if ($driverName) return $driverName;
            }
        }

        return null;
    }
}
