<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TravelSheetDayNightService
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

        $data_items = $params['data_items'] ?? 'time_a,position_a,odometer_a,time_b,position_b,odometer_b,duration,route_length,fuel_consumption,avg_fuel_consumption,fuel_cost,driver,trailer';

        $otherFields = $params['other'] ?? [];

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
            'dialog_report_other_dn_starts_hour' => $otherFields['dn_starts_hour'] ?? ($params['dn_starts_hour'] ?? '08'),
            'dialog_report_other_dn_starts_minute' => $otherFields['dn_starts_minute'] ?? ($params['dn_starts_minute'] ?? '00'),
            'dialog_report_other_dn_ends_hour' => $otherFields['dn_ends_hour'] ?? ($params['dn_ends_hour'] ?? '18'),
            'dialog_report_other_dn_ends_minute' => $otherFields['dn_ends_minute'] ?? ($params['dn_ends_minute'] ?? '00'),
            'dialog_report_filter' => 2,
            'dialog_report_type' => 'travel_sheet_dn_save',
            'dialog_report_data_item_list' => is_array($data_items) ? implode(',', $data_items) : $data_items,
            'pass_key' => 'AAA21A609BFD46C1437E01867D22913B'
        ];

        $keys = [];
        $totalSum = 0;
        $savedOkSum = 0;

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
                Log::error("TravelSheetDN API Error: " . $response->status());
            }
        } catch (\Exception $e) {
            Log::error("TravelSheetDN API Exception: " . $e->getMessage());
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
            $table = 'travel_sheet_reports_dn2_api';

            // Base query for detail rows (type=0)
            $baseQuery = DB::table($table)
                ->whereIn('table_key', $keys)
                ->where('type', 0);

            $totalCount = $baseQuery->count();

            // Fetch paginated detail rows
            $results = $baseQuery
                ->orderBy('imei', 'asc')
                ->orderBy('start_time', 'asc')
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

            // Fetch names and drivers for resolution
            $imeis = $results->pluck('imei')->unique()->toArray();
            $objects = DB::table('gs_objects')
                ->whereIn('imei', $imeis)
                ->pluck('name', 'imei');

            // Fetch Day/Night totals (type 1 and 2) for the IMEIs in this page
            $totalsData = DB::table($table)
                ->whereIn('table_key', $keys)
                ->whereIn('imei', $imeis)
                ->whereIn('type', [1, 2])
                ->get()
                ->groupBy('imei');

            $finalData = [];
            $currentImei = null;

            foreach ($results as $row) {
                // If IMEI changed, we could append previous IMEI's totals here if needed, 
                // but since we are iterating through rows, we'll append totals after each IMEI's set of rows.
                
                // Add header/plate row if IMEI changed (or first row)
                if ($row->imei !== $currentImei) {
                    if ($currentImei !== null) {
                        // Append totals for previous IMEI
                        $this->appendTotals($finalData, $totalsData->get($currentImei), $objects[$currentImei] ?? $currentImei);
                    }
                    $currentImei = $row->imei;
                    $finalData[] = [
                        'is_header' => true,
                        'imei' => $row->imei,
                        'object' => $objects[$row->imei] ?? $row->imei,
                    ];
                }

                $finalData[] = $this->transformRow($row, $objects);
            }

            // Append totals for the last IMEI in the page
            if ($currentImei !== null) {
                $this->appendTotals($finalData, $totalsData->get($currentImei), $objects[$currentImei] ?? $currentImei);
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
            Log::error("TravelSheetDayNightService Error: " . $e->getMessage());
            throw $e;
        }
    }

    private function transformRow($row, $objects)
    {
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
            'start_time' => $row->start_time,
            'departed_from' => $posA,
            'start_odometer' => $row->start_odometer,
            'end_time' => $row->end_time,
            'arrived_at' => $posB,
            'end_odometer' => $row->end_odometer,
            'duration' => $row->duration,
            'distance_travelled' => $row->distance_travelled,
            'fuel_consumption' => $row->fuel_consumption,
            'avg_fuel_consumption' => $row->avg_fuel_consumption,
            'fuel_cost' => $row->fuel_cost,
            'driver' => $row->driver,
            'trailer' => $row->trailer,
            'type' => $row->type
        ];
    }

    private function appendTotals(&$finalData, $totals, $objectName)
    {
        if (!$totals) return;

        foreach ($totals as $t) {
            $label = ($t->type == 1) ? 'Total Day' : 'Total Night';
            $finalData[] = [
                'is_total' => true,
                'imei' => $t->imei,
                'object' => $objectName,
                'start_time' => '',
                'departed_from' => $label,
                'start_odometer' => '',
                'end_time' => '',
                'arrived_at' => '',
                'end_odometer' => '',
                'duration' => $t->duration,
                'distance_travelled' => $t->distance_travelled,
                'fuel_consumption' => $t->fuel_consumption,
                'avg_fuel_consumption' => $t->avg_fuel_consumption,
                'fuel_cost' => $t->fuel_cost,
                'driver' => $t->driver,
                'trailer' => $t->trailer,
                'type' => $t->type
            ];
        }
    }
}
