<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RouteDataSensorsReportService
{
    /**
     * Generate report by calling legacy API.
     */
    public function generate(array $params, int $userId)
    {
        $imeis = $params['imei'] ?? $params['imeis'] ?? [];
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

        $data_items = $params['data_items'] ?? 'time,position,speed,altitude,angle,battery,driver_id,driver_name,ignition,main_power,odometer';

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
            'dialog_report_type' => 'route_data_sensors_save',
            'dialog_report_data_item_list' => is_array($data_items) ? implode(',', $data_items) : $data_items,
            'dialog_report_sensor_list' => is_array($params['sensor_names'] ?? null) ? implode(',', $params['sensor_names']) : ($params['sensor_names'] ?? ($params['sensors'] ?? '')),
            'pass_key' => 'AAA21A609BFD46C1437E01867D22913B'
        ];

        $keys = [];
        $totalSum = 0;
        $savedOkSum = 0;

        // Parallel requests (1 per IMEI for maximum stability with dynamic sensors)
        $responses = [];
        foreach ($imeis as $imei) {
            $postData = $basePostData;
            $postData['dialog_report_object_list'] = $imei;
            $responses[$imei] = Http::asForm()->timeout(600)->post($apiUrl, $postData);
        }

        foreach ($responses as $imei => $response) {
            if ($response->successful()) {
                $jsonResponse = $response->json();
                if ($jsonResponse && isset($jsonResponse['key'])) {
                    $keys[] = $jsonResponse['key'];
                    $totalSum += (int) ($jsonResponse['total'] ?? 0);
                    $savedOkSum += (int) ($jsonResponse['saved_ok'] ?? 0);
                }
            } else {
                Log::error("RouteDataSensors API Error for $imei: " . $response->status());
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
    public function fetchByKeys(array $keys, $page = 1, $limit = 100)
    {
        try {
            $offset = ($page - 1) * $limit;
            $table = 'route_data_sensors_repotrs2_api'; // Corrected table name with typo from DB

            // 1. Fetch labels from the first row (type=0)
            $firstRow = DB::table($table)
                ->whereIn('table_key', $keys)
                ->where('type', 0)
                ->orderBy('id', 'asc')
                ->first();

            if (!$firstRow) {
                return [
                    'data' => [],
                    'total' => 0,
                    'current_page' => (int) $page,
                    'last_page' => 1,
                    'per_page' => (int) $limit
                ];
            }

            $sensorLabels = json_decode($firstRow->sensor_lables, true);
            if (!is_array($sensorLabels)) $sensorLabels = [];

            // Add Object to labels
            array_unshift($sensorLabels, 'Object');

            // Find POSITION index (case-insensitive)
            $posIdx = null;
            // Use translation if possible, or fallback to 'POSITION'
            $wantPos = 'POSITION';
            foreach ($sensorLabels as $idx => $lab) {
                if (strtoupper(trim((string)$lab)) === $wantPos) {
                    $posIdx = $idx;
                    break;
                }
            }
            // Fallback to stripos if exact match fails
            if ($posIdx === null) {
                foreach ($sensorLabels as $idx => $lab) {
                    if (stripos(trim((string)$lab), 'position') !== false) {
                        $posIdx = $idx;
                        break;
                    }
                }
            }

            // 2. Fetch total count
            $totalCount = DB::table($table)
                ->whereIn('table_key', $keys)
                ->where('type', 0)
                ->count();
 
            // 3. Fetch paginated rows
            $results = DB::table($table)
                ->whereIn('table_key', $keys)
                ->where('type', 0)
                ->orderBy('id', 'asc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            // Fetch object names in batch for IMEIs in current results
            $imeis = $results->pluck('imei')->unique()->toArray();
            $objectNames = DB::table('gs_objects')
                ->whereIn('imei', $imeis)
                ->pluck('name', 'imei')
                ->toArray();

            $finalData = [];
            foreach ($results as $row) {
                $vals = json_decode($row->sensor_values, true);
                if (!is_array($vals)) {
                    $vals = [$row->sensor_values];
                }

                // Prepend object name to values
                $objName = $objectNames[$row->imei] ?? $row->imei;
                array_unshift($vals, $objName);

                // Overwrite position value if index found
                if ($posIdx !== null) {
                    $vals[$posIdx] = $row->position;
                }

                $finalData[] = [
                    'id' => $row->id,
                    'values' => $vals
                ];
            }

            $lastPage = ($limit > 0) ? ceil($totalCount / $limit) : 1;

            return [
                'sensorLabels' => $sensorLabels,
                'labels' => $sensorLabels,
                'data' => $finalData,
                'total' => $totalCount,
                'current_page' => (int) $page,
                'last_page' => (int) $lastPage,
                'per_page' => (int) $limit
            ];

        } catch (\Exception $e) {
            Log::error("RouteDataSensorsService Error: " . $e->getMessage());
            throw $e;
        }
    }
}
