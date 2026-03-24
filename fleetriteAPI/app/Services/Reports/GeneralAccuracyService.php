<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GeneralAccuracyService
{
    /**
     * Generate General Accuracy report by sending parallel requests to legacy API.
     * 
     * @param array $params
     * @return array
     */
    public function generate(array $params)
    {
        $imeis = $params['imeis'] ?? $params['imei'] ?? [];
        if (is_string($imeis)) {
            $imeis = explode(',', $imeis);
        }
        $imeis = array_unique(array_filter(array_map('trim', (array) $imeis)));

        if (empty($imeis)) {
            return ['status' => 'error', 'message' => 'No objects selected'];
        }

        $dtf_parts = explode(' ', $params['date_from'] ?? $params['from'] ?? '');
        $dtt_parts = explode(' ', $params['date_to'] ?? $params['to'] ?? '');

        $date_from = $dtf_parts[0] ?? date('Y-m-d');
        $time_from = count($dtf_parts) > 1 ? explode(':', $dtf_parts[1]) : ['00', '00'];
        
        $date_to = $dtt_parts[0] ?? date('Y-m-d');
        $time_to = count($dtt_parts) > 1 ? explode(':', $dtt_parts[1]) : ['00', '00'];

        $data_items = $params['data_items'] ?? 'route_start,route_end,route_length,move_duration,stop_duration,stop_count,top_speed,avg_speed,overspeed_count,fuel_consumption,avg_fuel_consumption,fuel_cost,engine_work,engine_idle,odometer,engine_hours,driver,trailer';

        $userId = $params['user_id'] ?? auth()->id();

        // Map frontend params to legacy API params
        $legacyParams = [
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
            'dialog_report_type' => 'general_accuracy_save',
            'type' => 'General Accuracy',
            'dialog_report_data_item_list' => is_array($data_items) ? implode(',', $data_items) : $data_items,
            'pass_key' => 'AAA21A609BFD46C1437E01867D22913B'
        ];

        $apiUrl = 'https://nv.esoft-eg.com/func/process_api.php';
        $maxParallel = 50;
        $imeiChunks = array_chunk($imeis, 1);

        $keys = [];
        $results = [];

        foreach (array_chunk($imeiChunks, $maxParallel) as $batch) {
            $responses = Http::pool(function ($pool) use ($batch, $apiUrl, $legacyParams) {
                return array_map(function ($chunk) use ($pool, $apiUrl, $legacyParams) {
                    return $pool->asForm()->timeout(300)->post($apiUrl, array_merge($legacyParams, [
                        'dialog_report_object_list' => implode(',', $chunk)
                    ]));
                }, $batch);
            });

            foreach ($responses as $imei => $response) {
                if ($response->successful()) {
                    $data = $response->json();
                    $isOk = $data && isset($data['ok']) && $data['ok'];
                    $key = $data['key'] ?? null;
                    
                    if ($key) {
                        $keys[] = $key;
                    }
                    
                    $results[] = [
                        'imei' => $imei,
                        'status' => $isOk ? 'success' : 'error',
                        'key' => $key,
                        'message' => $data['error'] ?? ($isOk ? null : 'Unknown legacy error')
                    ];
                } else {
                    $results[] = [
                        'imei' => $imei,
                        'status' => 'failed',
                        'error' => $response->status()
                    ];
                }
            }
        }

        return [
            'status' => 'success',
            'keys' => $keys,
            'details' => $results
        ];
    }

    /**
     * Fetch the generated report data from the database.
     * 
     * @param array $keys
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function fetchData(array $keys, $page = 1, $perPage = 1000)
    {
        if (empty($keys)) {
            return ['data' => [], 'totals' => null];
        }

        $query = DB::table('general_information_reports2_api')
            ->whereIn('table_key', $keys)
            ->where('type', 0);

        // Fetch all matching data (for calculating totals)
        $allData = $query->get();

        $totals = [
            'stop_count' => 0,
            'top_speed' => 0,
            'overspeed_count' => 0,
            'fuel_consumption' => 0,
            'average_fuel' => 0,
            'fuel_cost' => 0,
            'engine_work' => 0,
            'engine_idle' => 0,
            'odometer' => 0,
            'engine_hours' => 0,
            'speed_limit' => 0
        ];

        foreach ($allData as $row) {
            $totals['stop_count'] += (int) $row->stop_count;
            $totals['top_speed'] += (int) $row->top_speed;
            $totals['overspeed_count'] += (int) $row->overspeed_count;
            $totals['fuel_consumption'] += (float) $row->fuel_consumption;
            $totals['average_fuel'] += (float) $row->average_fuel;
            $totals['fuel_cost'] += (float) $row->fuel_cost;
            $totals['engine_work'] += (int) $row->engine_work;
            $totals['engine_idle'] += (int) $row->engine_idle;
            $totals['odometer'] += (float) $row->odometer;
            $totals['engine_hours'] += (int) $row->engine_hours;
            $totals['speed_limit'] += (int) ($row->average_speed ?? 0);
        }


        // Return all data (legacy behavior before full pagination implementation)
        return [
            'data' => $allData,
            'totals' => $totals
        ];
    }
}


