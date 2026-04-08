<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UnderspeedReportService
{
    public function generateReport($params)
    {
        // Legacy API Payload for Underspeed report
        $payload = [
            'form_user_id' => $params['user_id'] ?? 16,
            'user_id' => $params['user_id'] ?? 16,
            'dialog_report_object_list' => is_array($params['imei']) ? implode(',', $params['imei']) : $params['imei'],
            'dialog_report_date_from' => $params['date_from'] ?? date('Y-m-01'),
            'dialog_report_hour_from' => $params['hour_from'] ?? '00',
            'dialog_report_minute_from' => $params['minute_from'] ?? '00',
            'dialog_report_date_to' => $params['date_to'] ?? date('Y-m-d'),
            'dialog_report_hour_to' => $params['hour_to'] ?? '23',
            'dialog_report_minute_to' => $params['minute_to'] ?? '59',
            'dialog_report_format' => 'html',
            'dialog_report_ignore_empty_reports' => 'false',
            'dialog_report_show_coordinates' => 'true',
            'dialog_report_show_addresses' => 'true',
            'dialog_report_markers_addresses' => 'false',
            'dialog_report_zones_addresses' => 'false',
            'dialog_report_stop_duration' => $params['stop_duration'] ?? 5,
            'dialog_report_speed_limit' => $params['speed_limit'] ?? 40,
            'dialog_report_filter' => 2,
            'dialog_report_type' => 'underspeed',
            'dialog_report_data_item_list' => 'time,start,end,duration,top_speed,avg_speed,underspeed_position,driver',
            'pass_key' => 'AAA21A609BFD46C1437E01867D22913B',
        ];

        try {
            Log::info('Underspeed Report Triggered', ['payload' => $payload]);
            $response = Http::asForm()->timeout(600)->post('https://nv.esoft-eg.com/func/process_api.php', $payload);
            $body = trim($response->body());

            // Log raw response to a dedicated file for debugging
            $logContent = "[" . date('Y-m-d H:i:s') . "] Payload: " . json_encode($payload) . "\nBody: " . $body . "\n" . str_repeat('-', 50) . "\n";
            file_put_contents(storage_path('logs/underspeed_debug.log'), $logContent, FILE_APPEND);

            // If body is empty after trim, return empty success early
            if (empty($body)) {
                return [
                    'status' => 'success',
                    'headers' => ['#', 'Start', 'End', 'Duration', 'Top speed', 'Average speed', 'Underspeed position', 'Driver'],
                    'rows' => [],
                    'type' => 'underspeed'
                ];
            }

            // Handling concatenated JSON blocks or garbage text
            // Find first { and last } to isolate the JSON object
            $first_brace = strpos($body, '{');
            $last_brace = strrpos($body, '}');

            if ($first_brace !== false && $last_brace !== false) {
                $candidate = substr($body, $first_brace, $last_brace - $first_brace + 1);
                
                // If we have multiple blocks concatenated like }{, take the last one
                if (strpos($candidate, '}{') !== false) {
                    $json_responses = explode('}{', $candidate);
                    $body = '{' . end($json_responses);
                } else {
                    $body = $candidate;
                }
            }

            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // If the body is just whitespace or empty, ignore it instead of failing
                if (empty(trim($body))) {
                    Log::info('Underspeed Report: Empty body received for an IMEI, ignoring.', ['payload' => $payload]);
                    return [
                        'status' => 'success',
                        'headers' => ['#', 'Start', 'End', 'Duration', 'Top speed', 'Average speed', 'Underspeed position', 'Driver'],
                        'rows' => [],
                        'type' => 'underspeed'
                    ];
                }

                Log::error('Underspeed Report Error: JSON decode failed.', [
                    'error' => json_last_error_msg(), 
                    'body_preview' => substr($body, 0, 500),
                    'full_body' => $body
                ]);
                return [
                    'status' => 'error', 
                    'message' => 'Failed to parse report data.',
                    'debug_raw_body' => substr($body, 0, 2000)
                ];
            }

            $rows = $data['rows'] ?? [];

            if (!empty($rows)) {
                // Get object name from imei
                $objectName = DB::table('gs_objects')->where('imei', $payload['dialog_report_object_list'])->value('name') ?? $payload['dialog_report_object_list'];
                
                // Prepend header row
                array_unshift($rows, [
                    'ROW_IS_HEADER', // Marker for frontend
                    "Vehicle: $objectName",
                    '', '', '', '', '', ''
                ]);
            }

            return [
                'status' => 'success',
                'headers' => $data['headers'] ?? ['#', 'Start', 'End', 'Duration', 'Top speed', 'Average speed', 'Underspeed position', 'Driver'],
                'rows' => $rows,
                'type' => 'underspeed'
            ];

        } catch (\Exception $e) {
            Log::error('Underspeed Report Exception: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Connection to reporting server failed.'];
        }
    }
}
