<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\UnderspeedReportService;
use Illuminate\Http\Request;

class UnderspeedController extends Controller
{
    protected $reportService;

    public function __construct(UnderspeedReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Placeholder init (if needed by frontend modal)
     */
    public function init(Request $request)
    {
        $hash_id = md5(uniqid(rand(), true));
        return response()->json(['status' => 'success', 'hash_id' => $hash_id]);
    }

    /**
     * Generate the underspeed report.
     */
    public function generate(Request $request)
    {
        $params = $request->all();
        $userId = $request->user()->id;
        $params['user_id'] = $userId;
        
        $reportData = $this->reportService->generateReport($params);

        if ($reportData['status'] === 'success') {
            return response()->json($reportData);
        }

        return response()->json($reportData, 500);
    }
}
