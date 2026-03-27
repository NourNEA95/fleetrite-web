<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\GeneralMergedService;
use Illuminate\Http\Request;

class GeneralMergedController extends Controller
{
    protected $service;

    public function __construct(GeneralMergedService $service)
    {
        $this->service = $service;
    }

    /**
     * Generate report
     */
    public function generate(Request $request)
    {
        $params = $request->all();
        $result = $this->service->generate($params);
        return response()->json($result);
    }

    /**
     * Fetch report data
     */
    public function fetch(Request $request)
    {
        $keys = $request->input('keys');
        $page = (int) $request->input('page', 1);
        $rows = (int) $request->input('rows', 50);

        if (empty($keys)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No keys provided'
            ], 400);
        }

        $result = $this->service->fetchData($keys, $page, $rows);
        return response()->json($result);
    }
}
