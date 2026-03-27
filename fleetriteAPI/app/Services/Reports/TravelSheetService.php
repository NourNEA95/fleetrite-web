<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TravelSheetService
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

        $data_items = $params['data_items'] ?? 'time_a,position_a,odometer_a,time_b,position_b,odometer_b,duration,route_length,fuel_consumption,avg_fuel_consumption,fuel_cost,driver,trailer,total';

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
            'dialog_report_filter' => '',
            'dialog_report_type' => 'travel_sheet_save',
            'dialog_report_data_item_list' => is_array($data_items) ? implode(',', $data_items) : $data_items,
            'pass_key' => 'AAA21A609BFD46C1437E01867D22913B'
        ];

        $keys = [];
        $totalSum = 0;
        $savedOkSum = 0;

        // Process in parallel via pool if needed, but for now single request for the chunk
        // ReportPropertiesModal sends 100 imeis at once.
        $batchPost = $basePostData;
        $batchPost['dialog_report_object_list'] = implode(',', $imeis);

        try {
            $response = Http::asForm()->timeout(300)->post($apiUrl, $batchPost);
            
            if ($response->successful()) {
                $jsonResponse = $response->json();
                if ($jsonResponse && isset($jsonResponse['key'])) {
                    $keys[] = $jsonResponse['key'];
                    $totalSum = (int) ($jsonResponse['total'] ?? 0);
                    $savedOkSum = (int) ($jsonResponse['saved_ok'] ?? 0);
                }
            } else {
                Log::error("TravelSheet API Error: " . $response->status());
            }
        } catch (\Exception $e) {
            Log::error("TravelSheet API Exception: " . $e->getMessage());
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
    public function fetchByKeys(array $keys, $page = 1, $limit = 100)
    {
        try {
            $offset = ($page - 1) * $limit;

            // Define modular table
            $table = 'travel_sheet_reports2_api';

            // Base query (no driver filters as requested to revert)
            $baseQuery = DB::table($table)
                ->whereIn('table_key', $keys);

            // Get total count
            $totalCount = $baseQuery->count();

            // Fetch paginated and sorted data
            $results = $baseQuery
                ->orderBy('imei', 'asc')
                ->orderBy('start_time', 'asc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            // Fetch all unique IMEIs and Drivers for name resolution to avoid N+1 queries
            $imeis = $results->pluck('imei')->unique()->toArray();
            $driverNames = $results->pluck('driver')->unique()->filter()->toArray();

            $objects = DB::table('gs_objects')
                ->whereIn('imei', $imeis)
                ->pluck('name', 'imei');

            $driversData = DB::table('gs_user_object_drivers')
                ->whereIn('driver_name', $driverNames)
                ->get()
                ->keyBy('driver_name');

            $groupIds = $driversData->pluck('group_id')->unique()->filter()->toArray();
            $groups = DB::table('gs_user_driver_groups')
                ->whereIn('group_id', $groupIds)
                ->pluck('group_name', 'group_id');

            // Transform data
            $transformed = $results->map(function ($row) use ($objects, $driversData, $groups) {
                $driver = $driversData->get($row->driver);
                $groupName = 'No Group';
                if ($driver && $driver->group_id && isset($groups[$driver->group_id])) {
                    $groupName = $groups[$driver->group_id];
                }

                // Append zones to positions if applicable (Legacy parity)
                $posA = $row->departed_from ?? '';
                $zoneA = trim((string)($row->user_zone_a ?? ''));
                if ($zoneA !== '' && strcasecmp($zoneA, 'NA') !== 0 && strcasecmp($zoneA, 'N/A') !== 0) {
                    $posA .= " - " . $zoneA;
                }

                $posB = $row->arrived_at ?? '';
                $zoneB = trim((string)($row->user_zone_b ?? ''));
                if ($zoneB !== '' && strcasecmp($zoneB, 'NA') !== 0 && strcasecmp($zoneB, 'N/A') !== 0) {
                    $posB .= " - " . $zoneB;
                }

                return [
                    'id' => $row->id,
                    'imei' => $row->imei,
                    'object' => $objects[$row->imei] ?? $row->imei,
                    'group' => $groupName,
                    'unit_group' => $row->unit_group ?? '',
                    'start_time' => $row->start_time,
                    'departed_from' => $posA,
                    'start_odometer' => $row->start_odometer,
                    'end_time' => $row->end_time,
                    'arrived_at' => $posB,
                    'end_odometer' => $row->end_odometer,
                    'top_speed' => $row->top_speed ?? '0',
                    'duration' => $row->duration,
                    'stop_duration' => $row->stop_duration,
                    'distance_travelled' => $row->distance_travelled,
                    'fuel_consumption' => $row->fuel_consumption,
                    'avg_fuel_consumption' => $row->avg_fuel_consumption,
                    'fuel_cost' => $row->fuel_cost,
                    'driver' => $row->driver,
                    'trailer' => $row->trailer,
                ];
            });

            $allRows = $baseQuery->get();

            $totalDist = 0;
            $totalDur = 0;
            $totalStopDur = 0;
            $totalFuel = 0;
            $topSpeed = 0;

            foreach ($allRows as $r) {
                $totalDist += (float) $r->distance_travelled;
                $totalDur += (int) $r->duration;
                $totalStopDur += (int) $r->stop_duration;
                $totalFuel += (float) $r->fuel_consumption;
                $topSpeed = max($topSpeed, (float) ($r->top_speed ?? 0));
            }

            $totalsRow = [
                'is_total' => true,
                'object' => 'Total:',
                'distance_travelled' => round($totalDist, 2),
                'duration' => (int) $totalDur,
                'stop_duration' => (int) $totalStopDur,
                'fuel_consumption' => round($totalFuel, 2),
                'top_speed' => $topSpeed,
                'id' => 0,
                'imei' => '',
                'group' => '',
                'start_time' => '',
                'departed_from' => '',
                'start_odometer' => '',
                'end_time' => '',
                'arrived_at' => '',
                'end_odometer' => '',
                'avg_fuel_consumption' => '',
                'fuel_cost' => '',
                'driver' => '',
                'trailer' => '',
            ];

            $lastPage = ($limit > 0) ? ceil($totalCount / $limit) : 1;

            return [
                'data' => $transformed,
                'totals' => $totalsRow,
                'total' => $totalCount,
                'current_page' => (int) $page,
                'last_page' => (int) $lastPage,
                'per_page' => (int) $limit
            ];

        } catch (\Exception $e) {
            Log::error("TravelSheetService Error: " . $e->getMessage());
            throw $e;
        }
    }
}
