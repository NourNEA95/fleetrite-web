<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrentPositionReportService
{
    public function generateReport($params)
    {
        // Legacy API Payload for Current Position report
        $payload = [
            'form_user_id' => $params['user_id'] ?? 16,
            'user_id' => $params['user_id'] ?? 16,
            'dialog_report_object_list' => is_array($params['imei']) ? implode(',', $params['imei']) : $params['imei'],
            'dialog_report_date_from' => $params['from'] ?? date('Y-m-01 00:00:00'),
            'dialog_report_hour_from' => '00',
            'dialog_report_minute_from' => '00',
            'dialog_report_date_to' => $params['to'] ?? date('Y-m-d H:i:s'),
            'dialog_report_hour_to' => '00',
            'dialog_report_minute_to' => '00',
            'dialog_report_format' => 'html',
            'dialog_report_ignore_empty_reports' => 'false',
            'dialog_report_show_coordinates' => 'true',
            'dialog_report_show_addresses' => 'false',
            'dialog_report_markers_addresses' => 'false',
            'dialog_report_zones_addresses' => 'false',
            'dialog_report_stop_duration' => $params['stop_duration'] ?? 5,
            'dialog_report_filter' => 2,
            'dialog_report_type' => $params['type'] ?? 'current_position',
            'dialog_report_data_item_list' => 'time,position,speed,altitude,angle,status,odometer,engine_hours,driver',
            'pass_key' => 'AAA21A609BFD46C1437E01867D22913B',
        ];

        // Include sensors if selected
        if (!empty($params['sensor_names'])) {
            $payload['dialog_report_sensor_list'] = is_array($params['sensor_names']) ? implode(',', $params['sensor_names']) : $params['sensor_names'];
        }

        try {
            $response = Http::asForm()->post('https://nv.esoft-eg.com/func/process_api.php', $payload);
            $body = $response->body();

            // The legacy system often returns multiple JSON blocks concatenated.
            // We split by "}{" to isolate the final valid JSON block.
            $json_responses = explode('}{', $body);
            if (count($json_responses) > 1) {
                $body = '{' . end($json_responses);
            }

            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Current Position Report Error: JSON decode failed.', ['error' => json_last_error_msg(), 'body' => $body]);
                return ['status' => 'error', 'message' => 'Failed to parse report data.'];
            }

            // Return structured data for the frontend
            return [
                'status' => 'success',
                'headers' => $data['headers'] ?? [],
                'rows' => $data['rows'] ?? [],
                'type' => 'current_position'
            ];

        } catch (\Exception $e) {
            Log::error('Current Position Report Exception: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Connection to reporting server failed.'];
        }
    }
}
