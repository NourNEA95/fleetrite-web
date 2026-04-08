<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DrivesStopsReportService
{
    /**
     * Generate report by calling legacy API (parallel, batch of 100).
     */
    public function generate(array $params, int $userId)
    {
        $imeis = $params['imeis'] ?? $params['imei'] ?? [];
        if (is_string($imeis)) {
            $imeis = explode(',', $imeis);
        }
        $imeis = array_unique(array_filter(array_map('trim', (array) $imeis)));

        if (empty($imeis)) {
            return ['status' => 'error', 'message' => 'No objects selected'];
        }

        $apiUrl = 'https://nv.esoft-eg.com/func/process_api.php';
        
        $dtf_parts = explode(' ', $params['date_from'] ?? '');
        $dtt_parts = explode(' ', $params['date_to'] ?? '');

        $date_from = $dtf_parts[0] ?? date('Y-m-d');
        $time_from = count($dtf_parts) > 1 ? explode(':', $dtf_parts[1]) : ['00', '00'];
        
        $date_to = $dtt_parts[0] ?? date('Y-m-d');
        $time_to = count($dtt_parts) > 1 ? explode(':', $dtt_parts[1]) : ['00', '00'];

        $basePostData = [
            'form_user_id' => $userId,
            'user_id' => $userId,
            'dialog_report_date_from' => $date_from,
            'dialog_report_hour_from' => $time_from[0],
            'dialog_report_minute_from' => trim($time_from[1] ?? '00'),
            'dialog_report_date_to' => $date_to,
            'dialog_report_hour_to' => $time_to[0],
            'dialog_report_minute_to' => trim($time_to[1] ?? '00'),
            'dialog_report_format' => 'html',
            'dialog_report_ignore_empty_reports' => 'false',
            'dialog_report_show_coordinates' => 'true',
            'dialog_report_show_addresses' => 'true',
            'dialog_report_markers_addresses' => 'false',
            'dialog_report_zones_addresses' => 'false',
            'dialog_report_stop_duration' => $params['stop_duration'] ?? 5,
            'dialog_report_speed_limit' => $params['speed_limit'] ?? '',
            'dialog_report_filter' => $params['filter'] ?? 2,
            'dialog_report_type' => 'drives_stops_save',
            'dialog_report_data_item_list' => $params['data_items'] ?? 'status,start,end,duration,distance,top_speed,average_speed,fuel_consumption,avg_fuel_consumption,fuel_cost,engine_idle,driver,trailer',
            'pass_key' => 'AAA21A609BFD46C1437E01867D22913B'
        ];

        // Split IMEIs into batches of 100
        $chunks = array_chunk($imeis, 100);
        $keys = [];
        $totalSum = 0;
        $savedOkSum = 0;

        $responses = [];
        foreach ($chunks as $chunkIndex => $chunk) {
            $postData = $basePostData;
            $postData['dialog_report_object_list'] = implode(',', $chunk);
            $responses[$chunkIndex] = Http::asForm()->timeout(600)->post($apiUrl, $postData);
        }

        foreach ($responses as $chunkIndex => $response) {
            if ($response->successful()) {
                $jsonResponse = $response->json();
                if ($jsonResponse && isset($jsonResponse['key'])) {
                    $keys[] = $jsonResponse['key'];
                    $totalSum += (int) ($jsonResponse['total'] ?? 0);
                    $savedOkSum += (int) ($jsonResponse['saved_ok'] ?? 0);
                }
            } else {
                Log::error("DrivesStops API Error for batch $chunkIndex: " . $response->status());
            }
        }

        return [
            'status' => count($keys) > 0 ? 'success' : 'error',
            'message' => count($keys) > 0 ? '' : 'API Error: Missing key or invalid response from legacy system',
            'keys' => $keys,
            'total' => $totalSum,
            'saved_ok' => $savedOkSum
        ];
    }

    /**
     * Fetch report data using stored keys.
     */
    public function fetchByKeys(array $keys, $page = 1, $limit = 10000)
    {
        try {
            $offset = ($page - 1) * $limit;
            $table = 'drives_stops_reports2_api';

            // 1. Fetch total count (excluding summary rows)
            $totalCount = DB::table($table)
                ->whereIn('table_key', $keys)
                ->count();

            // 2. Fetch paginated rows ordered by imei and id
            $results = DB::table($table)
                ->whereIn('table_key', $keys)
                ->orderBy('imei', 'asc')
                ->orderBy('id', 'asc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            // 3. Batch fetch object names
            $uniqueImeis = $results->pluck('imei')->unique()->toArray();
            $objectNames = DB::table('gs_objects')
                ->whereIn('imei', $uniqueImeis)
                ->pluck('name', 'imei')
                ->toArray();

            // 4. Transform results
            $transformed = $results->map(function ($row) use ($objectNames) {
                $isTotal = ($row->type == 1);
                
                $distance = $row->distance;
                $duration = $row->duration;
                $engineIdle = $row->engine_idle;

                if ($isTotal) {
                    // Legacy structure for summary rows:
                    // 'end' column contains total duration string
                    // 'duration' column contains full summary string with move/stop/distance
                    $duration = $row->end;
                    
                    // Extract distance from string like "Move: ... Stop: ... 0.04 km km"
                    if (preg_match('/([\d\.]+)\s*km\s*km$/', $row->duration, $matches)) {
                        $distance = $matches[1];
                    }
                }

                return [
                    'id' => $row->id,
                    'imei' => $row->imei,
                    'object' => $objectNames[$row->imei] ?? $row->imei,
                    'status' => $isTotal ? 'TOTAL' : $row->status,
                    'start' => $isTotal ? '-' : $row->start,
                    'end' => $isTotal ? '-' : $row->end,
                    'duration' => $duration,
                    'length' => ($row->status === 'Stopped' || $row->status === 'متوقف') ? $row->stop_position : $distance,
                    'distance' => $distance, 
                    'stop_position' => $isTotal ? '' : $row->stop_position,
                    'top_speed' => $isTotal ? null : $row->top_speed,
                    'avg_speed' => $isTotal ? null : $row->average_speed,
                    'fuel_consumption' => $row->fuel_consumption,
                    'avg_fuel_consumption' => $isTotal ? null : $row->avg_fuel_consumption,
                    'fuel_cost' => $row->fuel_cost,
                    'engine_idle' => $engineIdle,
                    'driver' => $isTotal ? '-' : $row->driver,
                    'trailer' => $isTotal ? '-' : $row->trailer,
                    'type' => $row->type,
                    'is_total' => $isTotal,
                ];
            });

            // 5. Fetch totals row if it exists (type=1)
            $totals = DB::table($table)
                ->whereIn('table_key', $keys)
                ->where('type', 1)
                ->first();

            $lastPage = ($limit > 0) ? ceil($totalCount / $limit) : 1;

            return [
                'data' => $transformed,
                'totals' => $totals ? [
                    'duration' => $totals->duration,
                    'move_duration' => $totals->end, // Usually 'end' stores move duration in summary rows
                    'stop_duration' => $totals->duration,
                    'length' => $totals->distance,
                    'top_speed' => $totals->top_speed,
                    'avg_speed' => $totals->average_speed,
                    'fuel_consumption' => $totals->fuel_consumption,
                    'fuel_cost' => $totals->fuel_cost,
                ] : null,
                'total' => (int) $totalCount,
                'current_page' => (int) $page,
                'last_page' => (int) $lastPage,
                'per_page' => (int) $limit
            ];

        } catch (\Exception $e) {
            Log::error("DrivesStopsReportService Error: " . $e->getMessage());
            throw $e;
        }
    }
}
