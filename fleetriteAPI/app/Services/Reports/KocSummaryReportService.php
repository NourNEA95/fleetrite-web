<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KocSummaryReportService
{
    /**
     * Generate report by calling legacy API.
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
        
        $dtf_parts = explode(' ', $params['from'] ?? $params['date_from'] ?? '');
        $dtt_parts = explode(' ', $params['to'] ?? $params['date_to'] ?? '');

        $date_from = $dtf_parts[0] ?? date('Y-m-d');
        $time_from = count($dtf_parts) > 1 ? explode(':', $dtf_parts[1]) : ['00', '00'];
        
        $date_to = $dtt_parts[0] ?? date('Y-m-d');
        $time_to = count($dtt_parts) > 1 ? explode(':', $dtt_parts[1]) : ['00', '00'];

        $date_from_full = ($params['from'] ?? $params['date_from'] ?? date('Y-m-d')) . (str_contains(($params['from'] ?? ''), ':') ? '' : " {$time_from[0]}:{$time_from[1]}:00");
        $date_to_full = ($params['to'] ?? $params['date_to'] ?? date('Y-m-d')) . (str_contains(($params['to'] ?? ''), ':') ? '' : " {$time_to[0]}:{$time_to[1]}:59");

        $postData = [
            'form_user_id' => $userId,
            'user_id' => $userId,
            'dialog_report_object_list' => implode(',', $imeis),
            'dialog_report_date_from' => $date_from_full,
            'dialog_report_hour_from' => $time_from[0],
            'dialog_report_minute_from' => trim($time_from[1] ?? '00'),
            'dialog_report_date_to' => $date_to_full,
            'dialog_report_hour_to' => $time_to[0],
            'dialog_report_minute_to' => trim($time_to[1] ?? '00'),
            'dialog_report_format' => 'html',
            'dialog_report_ignore_empty_reports' => 'false',
            'dialog_report_show_coordinates' => 'true',
            'dialog_report_show_addresses' => 'false',
            'dialog_report_markers_addresses' => 'false',
            'dialog_report_zones_addresses' => 'false',
            'dialog_report_stop_duration' => $params['stop_duration'] ?? 5,
            'dialog_report_speed_limit' => $params['speed_limit'] ?? 80,
            'dialog_report_filter' => $params['filter'] ?? 2,
            'dialog_report_type' => 'overspeednew2_save',
            'dialog_report_data_item_list' => 'time,start,end,move_duration,route_length,stop_duration,stop_count,top_speed,avg_speed,overspeed_count,fuel_consumption,avg_fuel_consumption,fuel_cost,engine_work,engine_idle,odometer,engine_hours,driver,trailer',
            'pass_key' => 'AAA21A609BFD46C1437E01867D22913B'
        ];

        try {
            $response = Http::asForm()->timeout(600)->post($apiUrl, $postData);

            if ($response->successful()) {
                $jsonResponse = $response->json();
                if ($jsonResponse && isset($jsonResponse['key'])) {
                    return [
                        'status' => 'success',
                        'keys' => [$jsonResponse['key']],
                        'total' => (int) ($jsonResponse['total'] ?? 0),
                        'saved_ok' => (int) ($jsonResponse['saved_ok'] ?? 0)
                    ];
                }
                return ['status' => 'error', 'message' => 'Legacy API error: missing key. Body: ' . substr($response->body(), 0, 200)];
            }
            
            return ['status' => 'error', 'message' => 'Legacy API error: ' . $response->status() . '. Body: ' . substr($response->body(), 0, 200)];

        } catch (\Exception $e) {
            Log::error("KOC Summary Service Exception: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Fetch report data using stored keys.
     */
    public function fetchByKeys(array $keys, $page = 1, $limit = 10000)
    {
        try {
            $offset = ($page - 1) * $limit;
            $table = 'koc_summary_reports_api';

            $totalCount = DB::table($table)
                ->whereIn('table_key', $keys)
                ->count();

            $results = DB::table($table)
                ->whereIn('table_key', $keys)
                ->orderBy('imei', 'asc')
                ->orderBy('start_time', 'asc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            $transformed = $results->map(function ($row) {
                return [
                    'id' => $row->id,
                    'imei' => $row->imei,
                    'plate_number' => $row->plate_number,
                    'location' => $row->overspeed_location . ($row->zone ? ' - ' . $row->zone : ''),
                    'heading' => $row->vehicle_heading_locaton,
                    'recorded_speed' => $row->recorded_speed,
                    'speed_limit' => $row->allowed_speed_limit,
                    'start_time' => $row->start_time,
                    'end_time' => $row->end_time,
                    'os_duration' => $row->os_duraton, // Typo from DB
                    'os_distance' => $row->os_distance,
                    'seat_belt' => $row->seat_belt_usage,
                    'tamper' => $row->temper_signs, // Typo from DB
                    'harsh_accel' => $row->remarks_harsh_accelation, // Typo from DB
                    'harsh_brak' => $row->harsh_brak,
                    'harsh_corn' => $row->harsh_corn,
                    'os_count' => $row->overspeed_count,
                    'total_violation' => $row->total_violation,
                    'type' => 0
                ];
            });

            $lastPage = ($limit > 0) ? ceil($totalCount / $limit) : 1;

            return [
                'data' => $transformed,
                'total' => (int) $totalCount,
                'current_page' => (int) $page,
                'last_page' => (int) $lastPage,
                'per_page' => (int) $limit
            ];

        } catch (\Exception $e) {
            Log::error("KocSummaryReportService Fetch Error: " . $e->getMessage());
            throw $e;
        }
    }
}
