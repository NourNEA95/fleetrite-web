<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DriverTravelService
{
    /**
     * Generate report by calling legacy API.
     */
    public function generate(array $params, int $userId)
    {
        // For driver reports, we usually send all objects of the user
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

        $data_items = $params['data_items'] ?? 'time_a,position_a,odometer_a,time_b,position_b,odometer_b,duration,route_length,fuel_consumption,avg_fuel_consumption,fuel_cost,driver,trailer';

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
            'dialog_report_show_addresses' => 'false',
            'dialog_report_markers_addresses' => 'false',
            'dialog_report_zones_addresses' => 'false',
            'dialog_report_stop_duration' => $params['stop_duration'] ?? 5,
            'dialog_report_speed_limit' => $params['speed_limit'] ?? '',
            'dialog_report_filter' => $params['filter'] ?? 2,
            'dialog_report_type' => 'driver_travel_sheet_save',
            'dialog_report_data_item_list' => is_array($data_items) ? implode(',', $data_items) : $data_items,
            'dialog_report_driver_list' => $params['drivers'] ?? '',
            'pass_key' => 'AAA21A609BFD46C1437E01867D22913B'
        ];

        $keys = [];
        $totalSum = 0;
        $savedOkSum = 0;

        // Send all IMEIs in one batch to the legacy API
        $batchPost = $basePostData;
        $batchPost['dialog_report_object_list'] = implode(',', $imeis);

        try {
            $response = Http::asForm()->timeout(600)->post($apiUrl, $batchPost);
            
            if ($response->successful()) {
                $jsonResponse = $response->json();
                if ($jsonResponse && isset($jsonResponse['key'])) {
                    $keys[] = $jsonResponse['key'];
                    $totalSum = (int) ($jsonResponse['total'] ?? 0);
                    $savedOkSum = (int) ($jsonResponse['saved_ok'] ?? 0);
                }
            } else {
                Log::error("DriverTravel API Error: " . $response->status());
            }
        } catch (\Exception $e) {
            Log::error("DriverTravel API Exception: " . $e->getMessage());
        }

        return [
            'status' => count($keys) > 0 ? 'success' : 'error',
            'keys' => $keys,
            'total' => $totalSum,
            'saved_ok' => $savedOkSum
        ];
    }

    /**
     * Fetch report data using stored keys.
     */
    public function fetchByKeys(array $keys, $page = 1, $limit = 100, $driverIds = null)
    {
        try {
            $offset = ($page - 1) * $limit;
            $table = 'travel_sheet_reports2_api';

            // Base query
            $query = DB::table($table)
                ->whereIn('table_key', $keys)
                ->whereNotNull('driver')
                ->where('driver', '<>', '')
                ->where('driver', '<>', '0')
                ->where('driver', '<>', 'n/a');

            // Optional driver filter
            if (!empty($driverIds)) {
                $ids = array_filter(array_map('intval', explode(',', $driverIds)));
                
                if (!empty($ids)) {
                    $driverNames = DB::table('gs_user_object_drivers')
                        ->whereIn('driver_id', $ids)
                        ->pluck('driver_name')
                        ->toArray();
                    
                    if (!empty($driverNames)) {
                        $query->whereIn('driver', $driverNames);
                    }
                }
            }

            $totalCount = $query->count();

            // Fetch paginated rows
            $results = $query
                ->orderBy('driver', 'asc')
                ->orderBy('start_time', 'asc')
                ->orderBy('imei', 'asc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            if ($results->isEmpty()) {
                return [
                    'data' => [],
                    'total' => 0,
                    'current_page' => (int) $page,
                    'last_page' => 1,
                    'per_page' => (int) $limit
                ];
            }

            // Fetch metadata for resolution
            $imeis = $results->pluck('imei')->unique()->toArray();
            $objects = DB::table('gs_objects')
                ->whereIn('imei', $imeis)
                ->pluck('name', 'imei');

            $driverNamesUsed = $results->pluck('driver')->unique()->toArray();
            $driversMetadata = DB::table('gs_user_object_drivers')
                ->whereIn('driver_name', $driverNamesUsed)
                ->get()
                ->keyBy('driver_name');

            $userId = $results->first()->user_id ?? 0;
            $groupNames = DB::table('gs_user_driver_groups')
                ->where('user_id', $userId)
                ->pluck('group_name', 'group_id');

            $finalData = [];

            foreach ($results as $row) {
                $finalData[] = $this->transformRow($row, $objects, $driversMetadata, $groupNames);
            }

            $lastPage = ($limit > 0) ? ceil($totalCount / $limit) : 1;

            return [
                'data' => $finalData,
                'total' => $totalCount,
                'current_page' => (int) $page,
                'last_page' => (int) $lastPage,
                'per_page' => (int) $limit
            ];

        } catch (\Exception $e) {
            Log::error("DriverTravelService Error: " . $e->getMessage());
            throw $e;
        }
    }

    private function transformRow($row, $objects, $driversMetadata, $groupNames)
    {
        $driverName = $row->driver ?? 'N/A';
        $driverMeta = $driversMetadata->get($driverName);
        
        $driverDisplayName = $driverName;
        if ($driverMeta && !empty($driverMeta->driver_assign_id)) {
            $driverDisplayName .= " - " . $driverMeta->driver_assign_id;
        }

        $groupName = 'No Group';
        if ($driverMeta && !empty($driverMeta->group_id)) {
            $groupName = $groupNames->get($driverMeta->group_id) ?? 'No Group';
        }

        return [
            'id' => $row->id,
            'driver' => $driverDisplayName,
            'group' => $groupName,
            'imei' => $row->imei,
            'object' => $objects[$row->imei] ?? $row->imei,
            'start_time' => $row->start_time,
            'departed_from' => $row->departed_from ?? '',
            'start_odometer' => $row->start_odometer,
            'end_time' => $row->end_time,
            'arrived_at' => $row->arrived_at ?? '',
            'end_odometer' => $row->end_odometer,
            'top_speed' => $row->top_speed,
            'duration' => $row->duration,
            'stop_duration' => $row->stop_duration,
            'distance_travelled' => $row->distance_travelled,
            'fuel_consumption' => $row->fuel_consumption,
            'avg_fuel_consumption' => $row->avg_fuel_consumption,
            'fuel_cost' => $row->fuel_cost,
            'trailer' => $row->trailer
        ];
    }
}
