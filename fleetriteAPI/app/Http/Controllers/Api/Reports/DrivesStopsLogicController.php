<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\DrivesStopsLogicReportService;
use Illuminate\Http\Request;

class DrivesStopsLogicController extends Controller
{
    protected $service;

    public function __construct(DrivesStopsLogicReportService $service)
    {
        $this->service = $service;
    }

    /**
     * Generate Drives and Stops with Logic Sensors report.
     */
    public function generate(Request $request)
    {
        $userId = $request->user()->id ?? 16;
        $params = $request->all();

        $result = $this->service->generate($params, $userId);

        if (isset($result['status']) && $result['status'] === 'success') {
            return response()->json($result);
        }

        return response()->json($result, 400);
    }
}
