<?php

namespace App\Services\Reports;

use App\Models\GsObject;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DrivesStopsLogicReportService
{
    /**
     * Generate Drives and Stops with Logic Sensors report.
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
        
        $date_from_full = ($params['from'] ?? $params['date_from'] ?? date('Y-m-d H:i:s'));
        $date_to_full = ($params['to'] ?? $params['date_to'] ?? date('Y-m-d H:i:s'));

        $dtf_parts = explode(' ', $date_from_full);
        $dtt_parts = explode(' ', $date_to_full);

        $time_from = explode(':', $dtf_parts[1] ?? '00:00:00');
        $time_to = explode(':', $dtt_parts[1] ?? '23:59:59');

        $postData = [
            'form_user_id' => $userId,
            'user_id' => $userId,
            'dialog_report_object_list' => implode(',', $imeis),
            'dialog_report_date_from' => $dtf_parts[0],
            'dialog_report_hour_from' => $time_from[0],
            'dialog_report_minute_from' => trim($time_from[1] ?? '00'),
            'dialog_report_date_to' => $dtt_parts[0],
            'dialog_report_hour_to' => $time_to[0],
            'dialog_report_minute_to' => trim($time_to[1] ?? '00'),
            'dialog_report_format' => 'html',
            'dialog_report_ignore_empty_reports' => $params['ignore_empty_reports'] ?? 'false',
            'dialog_report_show_coordinates' => $params['show_coordinates'] ?? 'true',
            'dialog_report_show_addresses' => $params['show_addresses'] ?? 'false',
            'dialog_report_markers_addresses' => $params['markers_addresses'] ?? 'false',
            'dialog_report_zones_addresses' => $params['zones_addresses'] ?? 'false',
            'dialog_report_stop_duration' => $params['stop_duration'] ?? 5,
            'dialog_report_speed_limit' => $params['speed_limit'] ?? 80,
            'dialog_report_filter' => $params['filter'] ?? 2,
            'dialog_report_sensor_list' => is_array($params['sensor_names'] ?? null) ? implode(',', $params['sensor_names']) : ($params['sensor_names'] ?? ''),
            'dialog_report_type' => 'drives_stops_logic',
            'dialog_report_data_item_list' => 'status,start,end,duration,move_duration,stop_duration,distance_travelled,top_speed,avg_speed,fuel_consumption,avg_fuel_consumption,fuel_cost,engine_idle,driver,trailer',
            'pass_key' => 'AAA21A609BFD46C1437E01867D22913B'
        ];

        try {
            $response = Http::asForm()->timeout(900)->post($apiUrl, $postData);

            if ($response->successful()) {
                $body = trim($response->body());
                
                // Extract JSON object(s)
                $firstBracket = strpos($body, '{');
                $lastBracket = strrpos($body, '}');
                
                if ($firstBracket !== false && $lastBracket !== false && $lastBracket > $firstBracket) {
                    $jsonBody = substr($body, $firstBracket, $lastBracket - $firstBracket + 1);
                    
                    if (!mb_check_encoding($jsonBody, 'UTF-8')) {
                        $jsonBody = mb_convert_encoding($jsonBody, 'UTF-8', 'UTF-8');
                    }

                    // Split concatenated objects if any
                    $normalizedBody = str_replace('}{', '}###{', $jsonBody);
                    $rawChunks = explode('###', $normalizedBody);
                    $jsonChunks = [];
                    
                    foreach ($rawChunks as $chunk) {
                        $decoded = json_decode($chunk, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $jsonChunks[] = $decoded;
                        }
                    }

                    if (!empty($jsonChunks)) {
                        $objectNames = GsObject::whereIn('imei', $imeis)->pluck('name', 'imei')->toArray();
                        $vehiclesData = [];
                        
                        foreach ($jsonChunks as $index => $chunkData) {
                            $imei = $imeis[$index] ?? 'Unknown';
                            $name = $objectNames[$imei] ?? $imei;
                            
                            $vehiclesData[] = [
                                'imei' => $imei,
                                'name' => $name,
                                'headers' => $chunkData['headers'] ?? [],
                                'rows' => $chunkData['rows'] ?? [],
                                'summaryHeaders' => $chunkData['summaryHeaders'] ?? [],
                                'summaryValues' => $chunkData['summaryValues'] ?? []
                            ];
                        }

                        return [
                            'status' => 'success',
                            'vehicles' => $vehiclesData,
                            'period' => "{$date_from_full} - {$date_to_full}"
                        ];
                    }
                }
                
                return ['status' => 'error', 'message' => 'Invalid data format from legacy API'];
            }
            
            return ['status' => 'error', 'message' => 'Legacy API connection failed'];

        } catch (\Exception $e) {
            Log::error("Drives Stops Logic Service Exception: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Internal server error: ' . $e->getMessage()];
        }
    }
}
