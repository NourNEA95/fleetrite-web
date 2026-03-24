<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HistoryController extends Controller
{
    /**
     * Get historical route data for a specific vehicle
     */
    public function getRoute(Request $request, $imei)
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
            // Validate custom dates
            $request->validate([
                'from' => 'required',
                'to' => 'required',
            ]);
            // Convert from ISO/HTML5 datetime-local format if needed
            $from = date('Y-m-d H:i:s', strtotime($from));
            $to = date('Y-m-d H:i:s', strtotime($to));
        }

        // 3. Check if the dynamic table exists
        $tableName = "gs_object_data_" . $imei;
        if (!Schema::hasTable($tableName)) {
            return response()->json(['ok' => true, 'data' => []]);
        }

        // 4. Query data
        // We select the same fields as the TrackingController plus dt_tracker for ordering
        $points = DB::table($tableName)
            ->distinct()
            ->select([
                'dt_tracker',
                'lat',
                'lng',
                'altitude',
                'angle',
                'speed',
                'params'
            ])
            ->whereBetween('dt_tracker', [$from, $to])
            ->orderBy('dt_tracker', 'asc')
            ->get();

        // 5. Format results
        $formattedPoints = $points->map(function ($point) {
            $params = json_decode($point->params, true) ?: [];
            
            return [
                'lat' => (float)$point->lat,
                'lng' => (float)$point->lng,
                'speed' => round((float)$point->speed),
                'heading' => round((float)$point->angle),
                'altitude' => round((float)$point->altitude),
                'dt_tracker' => $point->dt_tracker,
                'params' => $params,
                // Synthetic fields for easier frontend use
                'ignition' => isset($params['acc']) ? ($params['acc'] ? 'On' : 'Off') : 'Unknown',
            ];
        });

        return response()->json([
            'ok' => true,
            'imei' => $imei,
            'count' => $formattedPoints->count(),
            'data' => $formattedPoints
        ]);
    }
}
