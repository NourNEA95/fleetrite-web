<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ObjectInfoReportService
{
    /**
     * Generate report by calling legacy API and return direct results.
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
            'dialog_report_type' => 'object_info',
            'dialog_report_data_item_list' => 'imei,group,transport_model,vin,plate_number,odometer,engine_hours,driver,trailer,gps_device,sim_card_number,company',
            'pass_key' => 'AAA21A609BFD46C1437E01867D22913B'
        ];

        try {
            $response = Http::asForm()->timeout(600)->post($apiUrl, $postData);

            if ($response->successful()) {
                $jsonResponse = $response->json();
                if ($jsonResponse && isset($jsonResponse['headers']) && isset($jsonResponse['rows'])) {
                    return [
                        'status' => 'success',
                        'headers' => $jsonResponse['headers'],
                        'rows' => $jsonResponse['rows']
                    ];
                }
                return ['status' => 'error', 'message' => 'Legacy API error: invalid response format or missing headers/rows. Body: ' . substr($response->body(), 0, 500)];
            }
            
            return ['status' => 'error', 'message' => 'Legacy API error: ' . $response->status() . '. Body: ' . substr($response->body(), 0, 200)];

        } catch (\Exception $e) {
            Log::error("Object Info Service Exception: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
