<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Reports\GeneralInformationService;

class GeneralInformationController extends Controller
{
    protected $reportService;

    public function __construct(GeneralInformationService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function generate(Request $request)
    {
        $request->validate([
            'imeis' => 'required_without:imei',
            'imei' => 'required_without:imeis',
            'date_from' => 'required_without:from',
            'from' => 'required_without:date_from',
            'date_to' => 'required_without:to',
            'to' => 'required_without:date_to',
            'data_items' => 'nullable'
        ]);

        $params = $request->all();
        
        // Map fields for service
        if (!isset($params['imeis']) && isset($params['imei'])) {
            $params['imeis'] = $params['imei'];
        }
        if (!isset($params['date_from']) && isset($params['from'])) {
            $params['date_from'] = $params['from'];
        }
        if (!isset($params['date_to']) && isset($params['to'])) {
            $params['date_to'] = $params['to'];
        }

        $userId = Auth::id() ?? 1; // Assuming default 1 for demo

        $result = $this->reportService->generate($params, $userId);

        if (($result['status'] ?? '') === 'error') {
            return response()->json($result, 400);
        }

        return response()->json([
            'status' => 'success',
            'keys' => $result['keys'] ?? [],
            'total' => $result['total'] ?? 0,
            'saved_ok' => $result['saved_ok'] ?? 0,
            'results' => $result['results'] ?? []
        ]);
    }

    public function fetch(Request $request)
    {
        $request->validate([
            'keys' => 'required',
            'data_items' => 'nullable'
        ]);

        $keysParam = $request->input('keys');
        $dataItems = $request->input('data_items');
        
        $dataItemsArray = $dataItems ? array_map('trim', explode(',', $dataItems)) : [];

        $payload = $this->reportService->fetch($keysParam, $dataItemsArray);

        return response()->json($payload);
    }
}
