<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GsUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TrackingController extends Controller
{
    /**
     * Get all objects for the authenticated user, grouped by their respective groups
     */
    public function getObjects(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['ok' => false, 'msg' => 'Unauthorized'], 401);
        }

        // Fetch objects joined with their group information
        $objects = DB::table('gs_user_objects')
            ->join('gs_objects', 'gs_user_objects.imei', '=', 'gs_objects.imei')
            ->leftJoin('gs_user_object_groups', 'gs_user_objects.group_id', '=', 'gs_user_object_groups.group_id')
            ->where('gs_user_objects.user_id', $user->id)
            ->select(
                'gs_objects.imei',
                'gs_objects.name',
                'gs_objects.lat',
                'gs_objects.lng',
                'gs_objects.altitude',
                'gs_objects.speed',
                'gs_objects.odometer',
                'gs_objects.angle',
                'gs_objects.dt_tracker',
                'gs_objects.dt_status',
                'gs_objects.params',
                'gs_objects.driver_id',
                'gs_user_object_groups.group_name',
                'gs_user_object_groups.group_id'
            )
            ->get();

        // Fetch ALL drivers to map them
        $allDrivers = DB::table('gs_user_object_drivers')->where('user_id', $user->id)->get();
        $driversByAssignId = $allDrivers->keyBy('driver_assign_id');
        $driversById = $allDrivers->keyBy('driver_id');

        // Fast lookup for the latest logbooks for all these IMEIs
        $imeis = $objects->pluck('imei')->toArray();
        $latestLogbooks = [];
        if (!empty($imeis)) {
            $latestLogbooksData = DB::table('gs_rilogbook_data')
                ->whereIn('imei', $imeis)
                ->select('imei', 'assign_id')
                ->whereIn('rilogbook_id', function($query) use ($imeis) {
                    $query->select(DB::raw('max(rilogbook_id)'))
                          ->from('gs_rilogbook_data')
                          ->whereIn('imei', $imeis)
                          ->groupBy('imei');
                })
                ->get();
            foreach ($latestLogbooksData as $lbl) {
                $latestLogbooks[$lbl->imei] = $lbl;
            }
        }

        // Format data for the frontend
        $formatted = $objects->map(function($obj) use ($driversByAssignId, $driversById, $latestLogbooks) {
            $params = json_decode($obj->params, true) ?? [];
            
            $driverName = null;
            
            // Priority 1: Latest logbook RFID Assign ID
            $logbook = $latestLogbooks[$obj->imei] ?? null;
            if ($logbook && $logbook->assign_id && isset($driversByAssignId[$logbook->assign_id])) {
                $driverName = $driversByAssignId[$logbook->assign_id]->driver_name;
            } 
            // Priority 2: gs_objects.driver_id
            elseif ($obj->driver_id && isset($driversById[$obj->driver_id])) {
                $driverName = $driversById[$obj->driver_id]->driver_name;
            }
            // Priority 3: driverUniqueId in params
            elseif (isset($params['driverUniqueId']) && isset($driversByAssignId[$params['driverUniqueId']])) {
                $driverName = $driversByAssignId[$params['driverUniqueId']]->driver_name;
            }


            // Geocoding logic with rounding (consistent with GridController)
            $address = '---';
            if ($obj->lat && $obj->lng) {
                $latKey = round((float)$obj->lat, 4);
                $lngKey = round((float)$obj->lng, 4);
                $cacheKey = "addr_{$latKey}_{$lngKey}";
                
                $address = Cache::remember($cacheKey, 60*60*24*7, function () use ($obj) {
                    try {
                        $response = Http::timeout(3)->get('http://gs.esoft-eg.com/reverse', [
                            'lat' => $obj->lat,
                            'lon' => $obj->lng,
                            'format' => 'jsonv2',
                            'accept-language' => 'en'
                        ]);
                        if ($response->successful()) {
                            return $response->json()['display_name'] ?? '---';
                        }
                    } catch (\Exception $e) {
                        Log::error("Tracking Geocode Error: " . $e->getMessage());
                    }
                    return '---';
                });
            }

            return [
                'imei' => $obj->imei,
                'name' => $obj->name,
                'lat' => (float)$obj->lat,
                'lng' => (float)$obj->lng,
                'speed' => round((float)($obj->speed ?? 0), 0),
                'altitude' => round((float)($obj->altitude ?? 0), 0),
                'angle' => round((float)($obj->angle ?? 0), 0),
                'heading' => (int)($obj->angle ?? 0),
                'dt_tracker' => $obj->dt_tracker,
                'dt_server' => $obj->dt_status,
                'group_name' => $obj->group_name ?: 'Ungrouped',
                'group_id' => $obj->group_id ?: 0,
                'status' => $this->calculateStatus($obj->dt_tracker, (float)($obj->speed ?? 0)),
                'ignition' => isset($params['acc']) ? ($params['acc'] ? 'On' : 'Off') : 'Unknown',
                'odometer' => round((float)($obj->odometer ?? 0) / 1000, 0), // Assuming meters to km
                'driver_name' => $driverName,
                'address' => $address,
                'params' => $params
            ];
        });

        // Group by group_name for the frontend if needed, or return flat and handle in Vue
        // The user wants groups under subtabs, so grouping in Vue is often more flexible.
        // We will return a flat list but with group info included.

        return response()->json([
            'ok' => true,
            'data' => $formatted
        ]);
    }

    public function getZones(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['ok' => false, 'msg' => 'Unauthorized'], 401);
        }

        $zones = DB::table('gs_user_zones')
            ->where('user_id', $user->id)
            ->select(
                'zone_id',
                'group_id',
                'zone_name',
                'zone_color',
                'zone_visible',
                'zone_name_visible',
                'zone_area',
                'zone_vertices',
                'zone_speed'
            )
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $zones
        ]);
    }

    private function calculateStatus($dt_tracker, $speed)
    {
        if (!$dt_tracker) return 'Offline';
        
        $lastSeen = new \DateTime($dt_tracker);
        $now = new \DateTime();
        $diff = abs($now->getTimestamp() - $lastSeen->getTimestamp());

        // If it's EXTREMELY old (e.g. > 7 days), then it's Offline
        if ($diff > 604800) return 'Offline'; 
        
        // If speed > 0 (or a small threshold), it's Moving, no matter how old (within 7 days)
        if ($speed > 2) return 'Moving';
        
        // If speed is 0 and it's quite old (> 24 hours), mark as Offline
        if ($speed <= 2 && $diff > 86400) return 'Offline';
        
        return 'Idle';
    }
}
