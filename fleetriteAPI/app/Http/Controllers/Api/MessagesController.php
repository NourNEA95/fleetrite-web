<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MessagesController extends Controller
{
    /**
     * Get historical message data for a specific vehicle with pagination and sorting
     */
    public function getMessages(Request $request, $imei)
    {
        $user = $request->user();

        // 1. Verify user has access to this vehicle
        $ownsVehicle = $user->userObjects()->where('imei', $imei)->exists();
        if (!$ownsVehicle) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized or vehicle not found'], 403);
        }

        // 2. Determine dates based on period or custom inputs
        $period = $request->input('period', 'today');
        $from = $request->input('from');
        $to = $request->input('to');

        if ($period !== 'custom') {
            switch ($period) {
                case 'yesterday':
                    $from = date('Y-m-d 00:00:00', strtotime('-1 day'));
                    $to = date('Y-m-d 23:59:59', strtotime('-1 day'));
                    break;
                case 'last_hour':
                    $from = date('Y-m-d H:i:s', strtotime('-1 hour'));
                    $to = date('Y-m-d H:i:s');
                    break;
                case 'current_week':
                    $from = date('Y-m-d 00:00:00', strtotime('monday this week'));
                    $to = date('Y-m-d 23:59:59');
                    break;
                case 'today':
                default:
                    $from = date('Y-m-d 00:00:00');
                    $to = date('Y-m-d 23:59:59');
                    break;
            }
        } else {
            $from = date('Y-m-d H:i:s', strtotime($from));
            $to = date('Y-m-d H:i:s', strtotime($to));
        }

        // 3. Check if the dynamic table exists
        $tableName = "gs_object_data_" . $imei;
        if (!Schema::hasTable($tableName)) {
            return response()->json([
                'ok' => true,
                'data' => [],
                'total' => 0,
                'current_page' => 1,
                'last_page' => 1
            ]);
        }

        // Pagination & Sorting Params
        $perPage = (int) $request->input('per_page', 50);
        $sidx = $request->input('sidx', 'dt_tracker');
        $sord = strtolower($request->input('sord', 'desc'));
        
        // Ensure sord is safe
        if (!in_array($sord, ['asc', 'desc'])) {
            $sord = 'desc';
        }

        // Ensure sidx is safe to prevent SQL injection
        $allowedColumns = ['id', 'dt_server', 'dt_tracker', 'lat', 'lng', 'altitude', 'angle', 'speed'];
        if (!in_array($sidx, $allowedColumns)) {
            $sidx = 'dt_tracker';
        }

        // 4. Query data
        $query = DB::table($tableName)
            ->select([
                'id',
                'dt_server',
                'dt_tracker',
                'lat',
                'lng',
                'altitude',
                'angle',
                'speed',
                'params'
            ])
            ->whereBetween('dt_tracker', [$from, $to])
            ->orderBy($sidx, $sord);

        $paginator = $query->paginate($perPage);

        // 5. Format results exactly as needed
        $formattedPoints = collect($paginator->items())->map(function ($point) {
            // we will format params in frontend, but send the raw string securely,
            // or we decode here and let frontend format it.
            // Since we need it as a comma separated string: 'key=value, key2=value2', we can do it on the backend, or frontend.
            // Returning the string as is ('{"acc":0, "priority": 0}') let's the frontend parse it. 
            // Wait, legacy stores it as json string. Let's send the string or decode it.
            $rawParams = $point->params;
            
            return [
                'id' => $point->id,
                'dt_server' => $point->dt_server,
                'dt_tracker' => $point->dt_tracker,
                'lat' => (float)$point->lat,
                'lng' => (float)$point->lng,
                'speed' => round((float)$point->speed),
                'angle' => round((float)$point->angle),
                'altitude' => round((float)$point->altitude),
                'params' => $rawParams
            ];
        });

        return response()->json([
            'ok' => true,
            'imei' => $imei,
            'data' => $formattedPoints,
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage()
        ]);
    }

    /**
     * Delete selected messages
     */
    public function deleteMessages(Request $request, $imei)
    {
        $user = $request->user();

        // 1. Verify user has access to this vehicle
        $ownsVehicle = $user->userObjects()->where('imei', $imei)->exists();
        if (!$ownsVehicle) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized or vehicle not found'], 403);
        }

        $ids = $request->input('ids', []);
        
        if (empty($ids)) {
            return response()->json(['ok' => false, 'message' => 'No messages provided to delete'], 400);
        }

        $tableName = "gs_object_data_" . $imei;
        if (!Schema::hasTable($tableName)) {
            return response()->json(['ok' => false, 'message' => 'Table not found'], 404);
        }

        DB::table($tableName)->whereIn('id', $ids)->delete();

        return response()->json(['ok' => true, 'message' => 'Messages deleted successfully']);
    }
}
