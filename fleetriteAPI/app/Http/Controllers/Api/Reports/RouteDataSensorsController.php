<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\RouteDataSensorsReportService;
use App\Traits\HandlesModularReportSessions;
use Illuminate\Http\Request;

class RouteDataSensorsController extends Controller
{
    use HandlesModularReportSessions;

    protected $service;

    public function __construct(RouteDataSensorsReportService $service)
    {
        $this->service = $service;
    }

    /**
     * Start a report session.
     */
    public function init(Request $request)
    {
        return $this->initializeSession($request, 'route_data_sensors');
    }

    /**
     * Generate the report by calling legacy API in parallel.
     */
    public function generate(Request $request)
    {
        $hashId = $request->input('hash_id');
        $sensorNames = $request->input('sensor_names', []);
        if (is_string($sensorNames)) {
            $sensorNames = explode(',', $sensorNames);
        }
        
        $mandatoryItems = ['time', 'position', 'speed', 'altitude', 'angle'];
        // Re-ordering sensors to common legacy order if possible: battery, driver_id, driver_name, ignition, main_power, odometer
        // But for generic sensors, we just append them.
        $dataItemsList = array_unique(array_merge($mandatoryItems, $sensorNames));

        $params = array_merge($request->all(), [
            'data_items' => implode(',', $dataItemsList),
        ]);
        $userId = $request->user()->id;

        $result = $this->service->generate($params, $userId);

        if ($result['status'] === 'success') {
            $this->appendKeysInternal($hashId, $result['keys']);
            return response()->json($result);
        }

        return response()->json($result, 400);
    }

    /**
     * Fetch paginated report data.
     */
    public function fetch(Request $request)
    {
        $hashId = $request->input('hash_id');
        $session = $this->getValidSession($hashId);

        if (!$session || empty($session->report_keys)) {
            return response()->json(['error' => 'Session not found or not generated'], 404);
        }

        $keys = explode(',', $session->report_keys);
        
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('per_page', $request->input('limit', 10000));

        $data = $this->service->fetchByKeys($keys, $page, $limit);

        return response()->json($data);
    }
}
