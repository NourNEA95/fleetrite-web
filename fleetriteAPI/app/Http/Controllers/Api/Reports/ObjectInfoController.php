<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\ObjectInfoReportService;
use Illuminate\Http\Request;

class ObjectInfoController extends Controller
{
    protected $service;

    public function __construct(ObjectInfoReportService $service)
    {
        $this->service = $service;
    }

    /**
     * Generate Object Information report directly.
     */
    public function generate(Request $request)
    {
        $userId = $request->user()->id ?? 16; // Default to 16 if not authenticated
        $params = $request->all();

        $result = $this->service->generate($params, $userId);

        if (isset($result['status']) && $result['status'] === 'success') {
            return response()->json($result);
        }

        return response()->json($result, 400);
    }
}
