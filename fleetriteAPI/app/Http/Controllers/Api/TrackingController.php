<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GsUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        // Fetch objects joined with their group information from gs_user_object_groups
        // We use a left join to include objects that might not have a group assigned
        $objects = DB::table('gs_user_objects')
            ->join('gs_objects', 'gs_user_objects.imei', '=', 'gs_objects.imei')
            ->leftJoin('gs_user_object_groups', 'gs_user_objects.group_id', '=', 'gs_user_object_groups.group_id')
            ->where('gs_user_objects.user_id', $user->id)
            ->select(
                'gs_objects.imei',
                'gs_objects.name',
                'gs_objects.lat',
                'gs_objects.lng',
                'gs_objects.speed',
                'gs_objects.angle as heading',
                'gs_objects.dt_tracker',
                'gs_objects.params',
                'gs_user_object_groups.group_name',
                'gs_user_object_groups.group_id'
            )
            ->get();

        // Format data for the frontend
        $formatted = $objects->map(function($obj) {
            $params = json_decode($obj->params, true) ?: [];
            return [
                'imei' => $obj->imei,
                'name' => $obj->name,
                'lat' => (float)$obj->lat,
                'lng' => (float)$obj->lng,
                'speed' => (float)$obj->speed,
                'heading' => (int)$obj->heading,
                'dt_tracker' => $obj->dt_tracker,
                'group_name' => $obj->group_name ?: 'Ungrouped',
                'group_id' => $obj->group_id ?: 0,
                'status' => $this->calculateStatus($obj->dt_tracker, (float)$obj->speed),
                'ignition' => isset($params['acc']) ? ($params['acc'] ? 'On' : 'Off') : 'Unknown',
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
