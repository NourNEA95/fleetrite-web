<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\GeneralAccuracyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GeneralAccuracyController extends Controller
{
    protected $reportService;

    public function __construct(GeneralAccuracyService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Generate report by sending parallel requests.
     */
    public function generate(Request $request)
    {
        // Accept both imei and imeis for frontend compatibility
        $validator = Validator::make($request->all(), [
            'imei' => 'nullable|array',
            'imeis' => 'nullable|array',
            'from' => 'nullable|string',
            'to' => 'nullable|string',
            'date_from' => 'nullable|string',
            'date_to' => 'nullable|string',
            'speed_limit' => 'nullable|string',
            'stop_duration' => 'nullable|integer',
            'data_items' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $params = $request->all();
        $params['imeis'] = $params['imeis'] ?? $params['imei'] ?? [];
        $params['date_from'] = $params['date_from'] ?? $params['from'];
        $params['date_to'] = $params['date_to'] ?? $params['to'];
        $params['user_id'] = auth()->id();

        if (empty($params['imeis'])) {
            return response()->json(['status' => 'error', 'message' => 'The imei field is required.'], 422);
        }

        $result = $this->reportService->generate($params);
        return response()->json($result);
    }

    /**
     * Fetch report data by keys.
     */
    public function fetch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'keys' => 'required|string',
            'page' => 'nullable|integer',
            'per_page' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $keys = explode(',', $request->keys);
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 1000);

        $result = $this->reportService->fetchData($keys, $page, $perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $result['data'],
            'totals' => $result['totals']
        ]);
    }
}
