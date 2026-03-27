<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GeneralInformationService
{
    private function parseDurationToSeconds($duration)
    {
        if (empty($duration) || $duration == '-') return 0;
        
        $seconds = 0;
        
        if (strpos($duration, ':') !== false) {
            $parts = explode(':', $duration);
            if (count($parts) == 3) {
                return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
            }
        }

        if (preg_match('/(\d+)\s*d/', $duration, $m)) $seconds += $m[1] * 86400;
        if (preg_match('/(\d+)\s*h/', $duration, $m)) $seconds += $m[1] * 3600;
        if (preg_match('/(\d+)\s*min/', $duration, $m)) $seconds += $m[1] * 60;
        if (preg_match('/(\d+)\s*s/', $duration, $m)) $seconds += $m[1];

        return $seconds;
    }

    private function formatSeconds($seconds, $includeDays = true)
    {
        if ($seconds < 0) $seconds = 0;
        
        $d = ($includeDays) ? floor($seconds / 86400) : 0;
        $h = ($includeDays) ? floor(($seconds % 86400) / 3600) : floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;

        $res = [];
        if ($d > 0) $res[] = "{$d} d";
        if ($h > 0 || $d > 0) $res[] = "{$h} h";
        if ($m > 0 || $h > 0 || $d > 0) $res[] = "{$m} min";
        $res[] = "{$s} s";

        return implode(' ', $res);
    }

    public function generate($params, $userId)
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

        $dtf_parts = explode(' ', $params['date_from'] ?? $params['from'] ?? '');
        $dtt_parts = explode(' ', $params['date_to'] ?? $params['to'] ?? '');

        $date_from = $dtf_parts[0] ?? date('Y-m-d');
        $time_from = count($dtf_parts) > 1 ? explode(':', $dtf_parts[1]) : ['00', '00'];
        
        $date_to = $dtt_parts[0] ?? date('Y-m-d');
        $time_to = count($dtt_parts) > 1 ? explode(':', $dtt_parts[1]) : ['00', '00'];

        $data_items = $params['data_items'] ?? 'route_start,route_end,route_length,move_duration,stop_duration,stop_count,top_speed,avg_speed,overspeed_count,fuel_consumption,avg_fuel_consumption,fuel_cost,engine_work,engine_idle,odometer,engine_hours,driver,trailer';

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
            'dialog_report_filter' => 2,
            'dialog_report_type' => 'general_save',
            'dialog_report_data_item_list' => is_array($data_items) ? implode(',', $data_items) : $data_items,
            'pass_key' => 'AAA21A609BFD46C1437E01867D22913B'
        ];

        // Parallel processing
        $maxParallel = 50;
        $imeiChunks = array_chunk($imeis, 1); // 1 imei per request for best distribution if process_api takes long, or chunk by larger for faster network

        $keys = [];
        $mergedResults = [];
        $totalSum = 0;
        $savedOkSum = 0;

        for ($i = 0; $i < count($imeiChunks); $i += $maxParallel) {
            $batchChunks = array_slice($imeiChunks, $i, $maxParallel);

            $batchPostDatas = [];
            foreach ($batchChunks as $chunk) {
                $batchPost = $basePostData;
                $batchPost['dialog_report_object_list'] = implode(',', $chunk);
                $batchPostDatas[] = $batchPost;
            }

            $responses = Http::pool(function ($pool) use ($batchPostDatas, $apiUrl) {
                $requests = [];
                foreach ($batchPostDatas as $pd) {
                    $requests[] = $pool->asForm()->timeout(300)->post($apiUrl, $pd);
                }
                return $requests;
            });

            foreach ($responses as $response) {
                if (!$response->successful()) {
                    Log::error('GeneralInfo API Error', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    continue;
                }

                $jsonResponse = $response->json();
                
                if ($jsonResponse && isset($jsonResponse['key'])) {
                    $keys[] = $jsonResponse['key'];
                    $totalSum += (int) ($jsonResponse['total'] ?? 0);
                    $savedOkSum += (int) ($jsonResponse['saved_ok'] ?? 0);
                    if (isset($jsonResponse['results']) && is_array($jsonResponse['results'])) {
                        $mergedResults = array_merge($mergedResults, $jsonResponse['results']);
                    }
                } else {
                    Log::error('GeneralInfo API Response Missing Key', [
                        'json' => $jsonResponse,
                        'body' => $response->body()
                    ]);
                }
            }
        }

        return [
            'status' => 'success',
            'keys' => array_values(array_unique($keys)),
            'total' => $totalSum,
            'saved_ok' => $savedOkSum,
            'results' => $mergedResults,
        ];
    }

    public function fetch($keysParam, $dataItemsArray, $id = null)
    {
        if ($id) {
            $generated = (array) DB::table('gs_user_reports_generated')->where('report_id', $id)->first();
            if (!empty($generated['front_keys'])) {
                $keysParam = $generated['front_keys'];
            } elseif (!empty($generated['report_file'])) {
                $keysParam = $generated['report_file'];
            }
        }

        if (is_string($keysParam)) {
            $keys = array_filter(array_map('trim', explode(',', $keysParam)));
        } else {
            $keys = $keysParam;
        }

        if (empty($keys)) {
            return ['data' => [], 'totals' => []];
        }

        $rows = DB::table('general_information_reports2_api')
            ->whereIn('table_key', $keys)
            ->where('type', 0)
            ->orderBy('id')
            ->get();

        $processedRows = [];
        
        $totals = [
            "route_length" => 0,
            "route_duration" => 0,
            "stop_duration" => 0,
            "stop_count" => 0,
            "top_speed" => 0,
            "overspeed_count" => 0,
            "fuel_consumption" => 0,
            "average_fuel" => 0,
            "fuel_cost" => 0,
            "engine_work" => 0,
            "engine_idle" => 0,
            "odometer" => 0,
            "engine_hours" => 0
        ];

        foreach ($rows as $row) {
            $rowObj = (array) $row;
            
            // Clean up numbers
            $rowObj['route_length_val'] = floatval(str_replace(' km', '', $rowObj['route_length'] ?? '0'));
            $totals['route_length'] += $rowObj['route_length_val'];

            $totals['route_duration'] += $this->parseDurationToSeconds($rowObj['route_duration'] ?? '');
            $totals['stop_duration'] += $this->parseDurationToSeconds($rowObj['stop_duration'] ?? '');
            
            $totals['stop_count'] += (int) ($rowObj['stop_count'] ?? 0);
            $totals['top_speed'] += (int) ($rowObj['top_speed'] ?? 0);
            $totals['overspeed_count'] += (int) ($rowObj['overspeed_count'] ?? 0);
            
            $totals['fuel_consumption'] += (float) ($rowObj['fuel_consumption'] ?? 0);
            $totals['average_fuel'] += (float) ($rowObj['average_fuel'] ?? 0);
            $totals['fuel_cost'] += (float) ($rowObj['fuel_cost'] ?? 0);

            $totals['engine_work'] += $this->parseDurationToSeconds($rowObj['engine_work'] ?? '');
            $totals['engine_idle'] += $this->parseDurationToSeconds($rowObj['engine_idle'] ?? '');
            
            $totals['odometer'] += (float) ($rowObj['odometer'] ?? 0);
            $totals['engine_hours'] += $this->parseDurationToSeconds($rowObj['engine_hours'] ?? '');

            $processedRows[] = $rowObj;
        }

        $totalsRow = [
            'is_total' => true,
            'object' => 'Total:',
            'status' => '-',
            'route_start' => '-',
            'route_end' => '-',
            'route_length' => round($totals['route_length'], 2) . ' km',
            'route_duration' => $this->formatSeconds($totals['route_duration']),
            'stop_duration' => $this->formatSeconds($totals['stop_duration']),
            'stop_count' => $totals['stop_count'],
            'top_speed' => $totals['top_speed'],
            'average_speed' => '-',
            'overspeed_count' => $totals['overspeed_count'],
            'fuel_consumption' => round($totals['fuel_consumption'], 2) . ' liters',
            'average_fuel' => round($totals['average_fuel'], 2) . ' liters',
            'fuel_cost' => round($totals['fuel_cost'], 2) . ' EUR',
            'engine_work' => $this->formatSeconds($totals['engine_work']),
            'engine_idle' => $this->formatSeconds($totals['engine_idle']),
            'odometer' => round($totals['odometer'], 2) . ' km',
            'engine_hours' => $this->formatSeconds($totals['engine_hours'], false),
            'driver' => '-',
            'flag_all_day' => '-'
        ];

        return [
            'data' => $processedRows,
            'totals' => $totalsRow
        ];
    }
}
