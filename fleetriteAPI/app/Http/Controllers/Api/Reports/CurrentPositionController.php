<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\CurrentPositionReportService;
use Illuminate\Http\Request;

class CurrentPositionController extends Controller
{
    protected $reportService;

    public function __construct(CurrentPositionReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function init(Request $request)
    {
        // Simple hash generation for tracking the modular session
        $hash_id = md5(uniqid(rand(), true));
        return response()->json(['status' => 'success', 'hash_id' => $hash_id]);
    }

    public function generate(Request $request)
    {
        $params = $request->all();
        $reportData = $this->reportService->generateReport($params);

        if ($reportData['status'] === 'success') {
            return response()->json($reportData);
        }

        return response()->json(['status' => 'error', 'message' => $reportData['message']], 500);
    }
}
