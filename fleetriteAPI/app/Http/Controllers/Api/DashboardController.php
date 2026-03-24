<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GsObject;
use App\Models\GsObjectService;
use App\Models\GsProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\GsUser;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get comprehensive dashboard data for a specific vehicle (IMEI)
     */
    public function getUnitStats(Request $request, $imei)
    {
        $user = $request->user();

        // 1. Verify access
        $valid = DB::table('gs_user_objects')->where('user_id', $user->id)->where('imei', $imei)->exists();
        if (!$valid) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 403);
        }

        // 2. Fetch basic object data
        $object = GsObject::where('imei', $imei)->first();
        if (!$object) return response()->json(['ok' => false, 'message' => 'Object data not found'], 404);

        $params = is_string($object->params) ? json_decode($object->params, true) : $object->params;
        $ignitionStr = (isset($params['acc']) && $params['acc'] == 1) ? 'ON' : 'OFF';

        // 3. Profile info & Unit Settings (Avoid session as it crashes in API)
        $profile = GsProfile::where('imei', $imei)->first();
        
        // Settings could be fetched from user table if needed: $user->unit_of_distance
        $unitDistance = 'km'; 
        $unitCapacity = 'l';

        // 4. Ignition Duration Calculation
        $lastIgnitionMessage = $this->getIgnitionDuration($imei);

        // 5. Calculate stats for the last 7 days
        $stats7Days = $this->calculateStats($imei, 7);

        // 6. Calculate charts for last 7 days (Daily stats)
        $dailyStats = $this->getDailyStats($imei, 7);

        // 7. Event Details
        $events = $this->getEventDetails($imei);

        // 8. Battery & Last Message
        $battery = $this->getBatteryStats($imei);
        
        $statusStr = 'Offline';
        if ($object->dt_server) {
            $diff = Carbon::parse($object->dt_server)->diffInMinutes(Carbon::now('UTC'));
            if ($diff < 10) {
                if ($object->speed > 5) $statusStr = 'Moving';
                else $statusStr = 'Stopped';
            }
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'imei' => $imei,
                'name' => $object->name,
                'lat' => (float)$object->lat,
                'lng' => (float)$object->lng,
                'speed' => (float)$object->speed,
                'heading' => (float)$object->angle,
                'odometer' => round((float)$object->odometer),
                'engine_hours' => round((float)$object->engine_hours / 3600, 1),
                'dt_tracker' => $object->dt_tracker,
                'dt_server' => $object->dt_server,
                'ignition' => $ignitionStr,
                'ignition_message' => $lastIgnitionMessage,
                'profile' => [
                    'registration' => $profile->plate_number ?? 'N/A',
                    'vin' => $profile->vin ?? 'N/A',
                    'brand' => $profile->brand ?? 'N/A',
                    'model' => $profile->model ?? 'N/A',
                    'color' => $profile->color ?? 'N/A',
                    'driver' => 'Unknown Driver', 
                ],
                'stats' => [
                    'distance_7' => $stats7Days['distance'],
                    'top_speed_7' => $stats7Days['top_speed'],
                    'avg_speed_7' => $stats7Days['avg_speed'],
                ],
                'events' => $events,
                'daily_stats' => $dailyStats,
                'battery' => $battery,
                'status' => $statusStr,
                'last_event' => $events['last_event'] ?? null
            ]
        ]);
    }

    private function getIgnitionDuration($imei)
    {
        $tableName = "gs_object_data_" . $imei;
        if (!Schema::hasTable($tableName)) return "N/A";

        $latest = DB::table($tableName)->orderBy('id', 'desc')->first();
        if (!$latest) return "N/A";

        $params = json_decode($latest->params, true);
        $currentAcc = isset($params['acc']) ? $params['acc'] : 0;

        $lastDifferent = DB::table($tableName)
            ->where('params', 'NOT LIKE', '%"acc":' . $currentAcc . '%')
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastDifferent) return "N/A";

        $diff = Carbon::parse($lastDifferent->dt_tracker)->diff(Carbon::now());
        return "{$diff->h} hours, {$diff->i} minutes, and {$diff->s} seconds ago";
    }

    private function calculateStats($imei, $days)
    {
        $tableName = "gs_object_data_" . $imei;
        if (!Schema::hasTable($tableName)) {
            return ['distance' => 0, 'top_speed' => 0, 'avg_speed' => 0];
        }

        $from = date('Y-m-d 00:00:00', strtotime("-$days days"));
        $to = date('Y-m-d 23:59:59');

        $data = DB::table($tableName)
            ->whereBetween('dt_tracker', [$from, $to])
            ->select(['speed', 'lat', 'lng', 'dt_tracker'])
            ->get();

        if ($data->isEmpty()) {
            return ['distance' => 0, 'top_speed' => 0, 'avg_speed' => 0];
        }

        $totalSpeed = 0;
        $maxSpeed = 0;
        $distance = 0;
        $prevPoint = null;

        foreach ($data as $point) {
            $speed = (float)$point->speed;
            $maxSpeed = max($maxSpeed, $speed);
            $totalSpeed += $speed;

            if ($prevPoint) {
                $distance += $this->haversineDistance($prevPoint->lat, $prevPoint->lng, $point->lat, $point->lng);
            }
            $prevPoint = $point;
        }

        return [
            'distance' => round($distance, 1),
            'top_speed' => round($maxSpeed, 1),
            'avg_speed' => round($totalSpeed / count($data), 1)
        ];
    }

    private function getDailyStats($imei, $days)
    {
        $tableName = "gs_object_data_" . $imei;
        if (!Schema::hasTable($tableName)) return [];

        $results = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dayName = date('D', strtotime($date));

            $points = DB::table($tableName)
                ->whereDate('dt_tracker', $date)
                ->select(['lat', 'lng', 'speed', 'dt_tracker'])
                ->orderBy('dt_tracker', 'asc')
                ->get();

            $dist = 0;
            $moveDuration = 0;
            $maxSpeed = 0;
            $overSpeedCount = 0;
            $prev = null;

            foreach ($points as $p) {
                if ($prev) {
                    $dist += $this->haversineDistance($prev->lat, $prev->lng, $p->lat, $p->lng);
                    $timeDiff = (strtotime($p->dt_tracker) - strtotime($prev->dt_tracker)) / 60;
                    if ((float)$p->speed > 5) $moveDuration += $timeDiff;
                }
                
                $s = (float)$p->speed;
                $maxSpeed = max($maxSpeed, $s);
                if ($s > 80) $overSpeedCount++;
                $prev = $p;
            }

            $results[] = [
                'day' => $dayName,
                'date' => $date,
                'distance' => round($dist, 2),
                'duration' => round($moveDuration),
                'top_speed' => round($maxSpeed),
                'overspeed_count' => $overSpeedCount
            ];
        }
        return $results;
    }

    private function getEventDetails($imei)
    {
        $now = date('Y-m-d');
        $week = date('Y-m-d', strtotime('-7 days'));
        $month = date('Y-m-d', strtotime('-30 days'));

        $lastEvent = DB::table('gs_user_events_data')
            ->where('imei', $imei)
            ->orderBy('event_id', 'desc')
            ->first();

        return [
            'today' => DB::table('gs_user_events_data')->where('imei', $imei)->whereDate('dt_tracker', $now)->count(),
            'week' => DB::table('gs_user_events_data')->where('imei', $imei)->whereBetween('dt_tracker', [$week, $now])->count(),
            'month' => DB::table('gs_user_events_data')->where('imei', $imei)->whereBetween('dt_tracker', [$month, $now])->count(),
            'avg' => round(DB::table('gs_user_events_data')->where('imei', $imei)->whereBetween('dt_tracker', [$month, $now])->count() / 30, 1),
            'last_event' => $lastEvent ? [
                'name' => $lastEvent->event_desc,
                'date' => $lastEvent->dt_tracker
            ] : null
        ];
    }

    private function getBatteryStats($imei)
    {
        $tableName = "gs_object_data_" . $imei;
        if (!Schema::hasTable($tableName)) return ['today' => 0, 'week' => 0, 'month' => 0];

        $getBat = function($date) use ($tableName) {
            $row = DB::table($tableName)
                ->where('dt_tracker', 'LIKE', $date . '%')
                ->orderBy('dt_tracker', 'desc')
                ->first();
            if (!$row) return 0;
            $p = json_decode($row->params, true);
            return $p['battery'] ?? 0;
        };

        return [
            'today' => round($getBat(date('Y-m-d')), 2),
            'week' => round($getBat(date('Y-m-d', strtotime('-7 days'))), 2),
            'month' => round($getBat(date('Y-m-d', strtotime('-30 days'))), 2)
        ];
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
