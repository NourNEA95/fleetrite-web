<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\GsObject;
use Carbon\Carbon;
use App\Services\TrackingService;

class GridController extends Controller
{
    public function getDetails(Request $request, $imei)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['ok' => false, 'msg' => 'Unauthorized'], 401);
        }

        // Lat/Lng passed from JS (fn_grid style)
        $lat = $request->query('lat');
        $lng = $request->query('lng');
        $near = $request->query('near', '---');
        $address = $request->query('address', '---');

        // 1. Primary Data (gs_objects)
        $object = GsObject::where('imei', $imei)->first();
        if (!$object) return response()->json(['ok' => false, 'msg' => 'Not found'], 404);

        // 2. Profile Data (gs_profile)
        $profile = DB::table('gs_profile')->where('imei', $imei)->first();

        // 3. RFID from Logbook (Legacy logic in fn_grid.php)
        $logbook = DB::table('gs_rilogbook_data')->where('imei', $imei)->orderBy('rilogbook_id', 'desc')->first();
        
        // 4. Driver info (Prioritize Logbook assign_id, fallback to gs_objects driver_id)
        $driver = null;
        
        if ($logbook && $logbook->assign_id) {
            $driver = DB::table('gs_user_object_drivers')
                ->where('user_id', $user->id)
                ->where('driver_assign_id', $logbook->assign_id)
                ->first();
        }

        if (!$driver && $object->driver_id) {
            $driver = DB::table('gs_user_object_drivers')
                ->where('driver_id', $object->driver_id)
                ->first();
        }

        $rfid = $driver->driver_assign_id ?? ($logbook->assign_id ?? '---');

        // Parse Params for sensors
        $params = json_decode($object->params, true) ?? [];
        $ignitionState = $params['acc'] ?? ($params['ignition'] ?? 'Off');
        $isIgnitionOn = ($ignitionState === '1' || $ignitionState === true || strtolower($ignitionState) === 'on');
        $ignition = $isIgnitionOn ? 'On' : 'Off';

        // Calculate Durations
        $now = Carbon::now();
        $userOffset = $user->timezone ?: '+ 0 hour';
        
        // Status Duration Logic
        $speed = (float)($object->speed ?? 0);
        $params = json_decode($object->params, true) ?? [];
        $ignitionState = $params['acc'] ?? ($params['ignition'] ?? 'Off');
        $isIgnitionOn = ($ignitionState === '1' || $ignitionState === true || strtolower($ignitionState) === 'on');
        
        $statusDt = $object->dt_last_stop; // Default Stopped
        if ($speed > 0) {
            $statusDt = $object->dt_last_move; // Moving
        } else if ($isIgnitionOn) {
            $statusDt = $object->dt_last_idle; // Idle
        }
        
        $now = Carbon::now();
        $statusTime = '---';
        if ($statusDt) {
            $diff = $now->diff(Carbon::parse($statusDt));
            $statusTime = sprintf('%d h %d min %d s', ($diff->days * 24) + $diff->h, $diff->i, $diff->s);
        }

        // Ignition Duration
        $ignDt = $isIgnitionOn ? $object->dt_ignition_on : $object->dt_ignition_off;
        $ignTime = '---';
        if ($ignDt) {
            $diff = $now->diff(Carbon::parse($ignDt));
            $ignTime = sprintf('%d h %d min %d s', ($diff->days * 24) + $diff->h, $diff->i, $diff->s);
        }

        // Reverse Geocoding with Caching
        $address = '---';
        if ($object->lat && $object->lng) {
            // Round to 4 decimal places for caching (approx 11 meters resolution) to increase cache hits
            $latKey = round((float)$object->lat, 4);
            $lngKey = round((float)$object->lng, 4);
            $cacheKey = "address_{$latKey}_{$lngKey}";

            $address = \Illuminate\Support\Facades\Cache::remember($cacheKey, 60*60*24*7, function () use ($object) {
                try {
                    $response = \Illuminate\Support\Facades\Http::timeout(5)->get('http://gs.esoft-eg.com/reverse', [
                        'lat' => $object->lat,
                        'lon' => $object->lng,
                        'format' => 'jsonv2',
                        'accept-language' => 'en'
                    ]);
                    
                    if ($response->successful()) {
                        $data = $response->json();
                        return $data['display_name'] ?? '---';
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Reverse Geocoding API Error: " . $e->getMessage());
                }
                return '---';
            });
        }



        // 5. Daily Statistics
        $trackingService = app(TrackingService::class);
        $nowUser = Carbon::now('UTC')->modify($userOffset);
        $todayStartUser = $nowUser->copy()->startOfDay()->toDateTimeString();
        $inverseOffset = strpos($userOffset, '+') !== false ? str_replace('+', '-', $userOffset) : str_replace('-', '+', $userOffset);
        $todayStartUtc = Carbon::parse($todayStartUser)->modify($inverseOffset)->toDateTimeString();
        $nowUtc = Carbon::now('UTC')->toDateTimeString();

        $userSettings = ['timezone' => $userOffset, 'units' => $user->units ?? 'km', 'currency' => $user->currency ?? 'USD'];
        $stats = $trackingService->getRoute($imei, $todayStartUtc, $nowUtc, 10, true, $userSettings);

        // 6. Recent Events
        $events = DB::table('gs_user_last_events_data')
            ->where('user_id', $user->id)
            ->where('imei', $imei)
            ->where('dt_server', '>=', $todayStartUtc)
            ->orderBy('event_id', 'desc')
            ->limit(8)
            ->get()
            ->map(function($e) use ($userOffset) {
                return [
                    'type' => $e->type,
                    'time' => Carbon::parse($e->dt_server)->modify($userOffset)->toDateTimeString()
                ];
            });

        // 7. Road Speed Limit
        $lon = (float)$object->lng;
        $lat = (float)$object->lat;
        $roadSpeedLimit = $object->speed_limit ?? 0;
        try {
            $speedLimitRow = DB::table('kuwait_streets')
                ->select('maxspeed')
                ->selectRaw('ST_Distance_Sphere(geom, POINT(?, ?)) AS distance_m', [$lon, $lat])
                ->whereNotNull('geom')
                ->orderBy('distance_m')
                ->first();
                
            if ($speedLimitRow && !empty($speedLimitRow->maxspeed)) {
                $roadSpeedLimit = $speedLimitRow->maxspeed;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Road speed limit error: ' . $e->getMessage());
        }

        return response()->json([
            'ok' => true,
            'device' => [
                'protocol' => $object->protocol ?? '---',
                'active' => $object->active ?? '---',
                'last_date' => Carbon::parse($object->dt_tracker)->modify($userOffset)->toDateTimeString(),
                'server_date' => Carbon::parse($object->dt_status)->modify($userOffset)->toDateTimeString(),
                'rfid' => $rfid,
                'imei' => $imei,
                'sim_number' => $object->sim_number ?? '---',
                'speed_limit' => $roadSpeedLimit
            ],
            'vehicle' => [
                'type' => $profile->type ?? '---',
                'vin' => $object->vin ?? ($profile->vin ?? '---'),
                'brand' => $profile->brand ?? '---',
                'model' => $object->model ?? ($profile->model ?? '---'),
                'year' => $profile->year ?? '---',
                'color' => $profile->color ?? '---',
                'insurance_expiry' => $profile->ex_day ?? '---',
                'image' => "uploads/{$imei}.png"
            ],
            'stats' => [
                'total_odometer' => round($object->odometer ?? 0, 0),
                'route_length' => round($stats['route_length'] ?? 0, 2),
                'move_duration' => $stats['drives_duration'] ?? '0 h 0 min',
                'stop_duration' => $stats['stops_duration'] ?? '0 h 0 min',
                'top_speed' => round($stats['top_speed'] ?? 0, 0),
                'avg_speed' => round($stats['avg_speed'] ?? 0, 0),
                'fuel_consumption' => round($stats['fuel_consumption'] ?? 0, 2),
                'fuel_cost' => round($stats['fuel_cost'] ?? 0, 2),
                'engine_work' => $stats['engine_work'] ?? '0 h 0 min',
                'engine_idle' => $stats['engine_idle'] ?? '0 h 0 min',
            ],

            'events' => $events,
            'driver' => [
                'name' => $driver->driver_name ?? '---',
                'address' => $driver->driver_address ?? '---',
                'phone' => $driver->driver_phone ?? '---',
                'email' => $driver->driver_email ?? '---'
            ],
            'sensors' => [
                'ignition' => $ignition,
                'ignition_time' => $ignTime,
                'status_time' => $statusTime,
                'battery' => isset($params['bat']) ? round((float)$params['bat'], 2) : (isset($params['battery']) ? round((float)$params['battery'], 2) : '---'),
                'power' => isset($params['pow']) ? round((float)$params['pow'], 2) : (isset($params['power']) ? round((float)$params['power'], 2) : '---')
            ],
            'location' => [
                'address' => $address,
                'near' => $near,
                'lat' => (float)$object->lat,
                'lng' => (float)$object->lng,
                'altitude' => $object->altitude ?? 0,
                'angle' => $object->angle ?? 0,
                'speed' => round((float)($object->speed ?? 0), 0)
            ]


        ]);


    }
}
