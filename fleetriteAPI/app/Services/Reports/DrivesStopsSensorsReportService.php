<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DrivesStopsSensorsReportService
{
    /**
     * Generate Drives and Stops with Sensors report.
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

        $date_from_full = ($params['from'] ?? $params['date_from'] ?? date('Y-m-d H:i:s'));
        $date_to_full = ($params['to'] ?? $params['date_to'] ?? date('Y-m-d H:i:s'));

        $time_from = explode(':', $dtf_parts[1] ?? '00:00:00');
        $time_to = explode(':', $dtt_parts[1] ?? '23:59:59');

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
            'dialog_report_other_dn_starts_hour' => '00',
            'dialog_report_other_dn_starts_minute' => '00',
            'dialog_report_other_dn_ends_hour' => '00',
            'dialog_report_other_dn_ends_minute' => '00',
            'dialog_report_other_rag_low_score' => '',
            'dialog_report_other_rag_high_score' => '',
            'dialog_report_schedule_period_daily' => 'on',
            'dialog_report_schedule_period_weekly' => 'on',
            'dialog_report_schedule_email_address' => '',
            'dialog_report_filter' => $params['filter'] ?? 2,
            'dialog_report_driver_list' => '',
            'dialog_report_marker_list' => '',
            'dialog_report_zone_list' => '',
            'dialog_report_sensor_list' => '',
            'dialog_report_name2' => '',
            'dialog_report_name3' => '',
            'dialog_report_name' => '',
            'dialog_report_type' => 'drives_stops_sensors',
            'dialog_report_data_item_list' => 'status,start,end,duration,move_duration,stop_duration,distance_travelled,top_speed,avg_speed,fuel_consumption,avg_fuel_consumption,fuel_cost,engine_idle,driver,trailer',
            'pass_key' => 'AAA21A609BFD46C1437E01867D22913B'
        ];

        try {
            $response = Http::asForm()->timeout(600)->post($apiUrl, $postData);

            if ($response->successful()) {
                $body = trim($response->body());
                
                // Try to find the first '{' and last '}' to extract the JSON object
                $firstBracket = strpos($body, '{');
                $lastBracket = strrpos($body, '}');
                
                if ($firstBracket !== false && $lastBracket !== false && $lastBracket > $firstBracket) {
                    $jsonBody = substr($body, $firstBracket, $lastBracket - $firstBracket + 1);
                    
                    // Force UTF-8 encoding to prevent decode issues with non-UTF8 characters from legacy API
                    if (!mb_check_encoding($jsonBody, 'UTF-8')) {
                        $jsonBody = mb_convert_encoding($jsonBody, 'UTF-8', 'UTF-8');
                    }

                    // The logic API sometimes returns multiple concatenated JSON objects like {obj1}{obj2}
                    // We need to split them and merge their data.
                    $jsonChunks = [];
                    $bodyToSplit = $jsonBody;
                    
                    // Simple split by }{ which is the boundary between concatenated objects
                    // We use a unique delimiter to avoid splitting inside string values (rare but possible)
                    $normalizedBody = str_replace('}{', '}###{', $bodyToSplit);
                    $rawChunks = explode('###', $normalizedBody);
                    
                    foreach ($rawChunks as $chunk) {
                        $decoded = json_decode($chunk, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $jsonChunks[] = $decoded;
                        }
                    }

                    if (!empty($jsonChunks)) {
                        // Fetch object names for mapping
                        $objectNames = \App\Models\GsObject::whereIn('imei', $imeis)
                            ->pluck('name', 'imei')
                            ->toArray();

                        $vehiclesData = [];
                        $firstHeaders = $jsonChunks[0]['headers'] ?? [];
                        
                        // If we have multiple IMEIs, the chunks usually correspond to each IMEI in order
                        // If not, we'll try to find any identifying info in the chunk (though legacy often doesn't provide it)
                        // Most reliable: the chunks match the request order.
                        foreach ($jsonChunks as $index => $chunkData) {
                            $imei = $imeis[$index] ?? 'Unknown';
                            $name = $objectNames[$imei] ?? $imei;
                            
                            $vehiclesData[] = [
                                'imei' => $imei,
                                'name' => $name,
                                'headers' => $chunkData['headers'] ?? $firstHeaders,
                                'rows' => $chunkData['rows'] ?? [],
                                'summaryHeaders' => $chunkData['summaryHeaders'] ?? [],
                                'summaryValues' => $chunkData['summaryValues'] ?? []
                            ];
                        }

                        return [
                            'status' => 'success',
                            'headers' => $firstHeaders,
                            'vehicles' => $vehiclesData
                        ];
                    }

                    $jsonError = json_last_error_msg();
                    Log::error("Legacy API JSON Decode Error for DrivesStopsSensors: {$jsonError}. Body length: " . strlen($body) . ". Save to storage/drives_stops_fail.json");
                    file_put_contents(storage_path('drives_stops_fail.json'), $body);
                } else {
                    Log::error("Legacy API Truncated/Invalid Response for DrivesStopsSensors. No valid JSON object found. Body length: " . strlen($body) . ". Save to storage/drives_stops_fail.json");
                    file_put_contents(storage_path('drives_stops_fail.json'), $body);
                }
                
                // If we detected it might be a truncation/size issue
                if ($response->header('Content-Length') && strlen($body) < (int)$response->header('Content-Length')) {
                    return ['status' => 'error', 'type' => 'timeout', 'message' => 'Report data was truncated during transfer. Please reduce the number of vehicles or the selected period.'];
                }

                return ['status' => 'error', 'message' => '[V2] Invalid JSON or data too large. Body: ' . substr($body, 0, 1000)];
            }
            
            Log::error("Legacy API Connection Failed for DrivesStopsSensors. Status: " . $response->status() . " Body: " . substr($response->body(), 0, 500));
            return ['status' => 'error', 'message' => 'Legacy API error: ' . $response->status()];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return ['status' => 'error', 'type' => 'timeout', 'message' => 'Report data is too large. Please reduce the number of vehicles or the selected period.'];
        } catch (\Exception $e) {
            Log::error("Drives Stops Sensors Service Exception: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
