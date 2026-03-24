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
        $imeis = $params['imeis'] ?? [];
        if (empty($imeis)) {
            return ['status' => 'error', 'message' => 'No IMEIs provided'];
        }

        // Map frontend params to legacy API params
        $legacyParams = [
            'dialog_report_date_from' => $params['date_from'],
            'dialog_report_date_to' => $params['date_to'],
            'dialog_report_speed_limit' => $params['speed_limit'] ?? '',
            'dialog_report_stop_duration' => $params['stop_duration'] ?? 5,
            'dialog_report_data_item_list' => is_array($params['data_items']) ? implode(',', $params['data_items']) : $params['data_items'],
            'dialog_report_type' => 'general_accuracy_save', // Specific type for Accuracy
            'type' => 'General Accuracy', // Per user request
            'dialog_report_format' => 'json',
            'user_id' => $params['user_id'] ?? auth()->id(),
            'form_user_id' => $params['user_id'] ?? auth()->id(),
            'pass_key' => config('services.legacy_api.pass_key', 'AAA21A609BFD46C1437E01867D22913B'),
        ];

        $apiUrl = 'https://nv.esoft-eg.com/func/process_api.php';
        $maxParallel = 50;
        $imeiChunks = array_chunk($imeis, 1);

        $keys = [];
        $results = [];

        foreach (array_chunk($imeiChunks, $maxParallel) as $batch) {
            $responses = Http::pool(function ($pool) use ($batch, $apiUrl, $legacyParams) {
                return array_map(function ($chunk) use ($pool, $apiUrl, $legacyParams) {
                    return $pool->as($chunk[0])->post($apiUrl, array_merge($legacyParams, [
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
            'engine_hours' => 0
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
        }

        // Return all data (legacy behavior before full pagination implementation)
        return [
            'data' => $allData,
            'totals' => $totals
        ];
    }
}


