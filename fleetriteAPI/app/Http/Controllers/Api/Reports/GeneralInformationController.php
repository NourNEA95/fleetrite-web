<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\GeneratedReport;
use App\Models\ModularReportSession;
use App\Services\Reports\GeneralInformationService;
use App\Traits\HandlesModularReportSessions;

class GeneralInformationController extends Controller
{
    use HandlesModularReportSessions;

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

        if (($result['status'] ?? '') === 'success' && !empty($result['keys'])) {
            $generatedReport = GeneratedReport::create([
                'user_id' => $userId,
                'dt_report' => now(),
                'name' => $params['name'] ?? 'General Information',
                'type' => 'general_information',
                'format' => $params['format'] ?? 'html',
                'front_keys' => implode(',', $result['keys']),
                'objects' => is_array($params['imeis']) ? count($params['imeis']) : (empty($params['imeis']) ? 0 : 1),
                'markers' => 0,
                'zones' => 0,
                'sensors' => 0,
                'schedule' => '',
                'filename' => '',
                'report_file' => ''
            ]);

            // Also append to session if hash_id is provided
            if ($request->has('hash_id')) {
                $this->appendKeysInternal($request->hash_id, $result['keys']);
            }

            return response()->json([
                'status' => 'success',
                'report_id' => $generatedReport->report_id,
                'keys' => $result['keys'] ?? [],
                'total' => $result['total'] ?? 0,
                'saved_ok' => $result['saved_ok'] ?? 0,
                'results' => $result['results'] ?? []
            ]);
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
        $keysParam = $request->input('keys');
        $id = $request->input('id');
        $hashId = $request->input('hash_id');
        $dataItems = $request->input('data_items');
        
        // Priority: hash_id > id > keys
        if ($hashId) {
            $session = ModularReportSession::where('hash_id', $hashId)
                ->where('user_id', Auth::id() ?? 1)
                ->first();
            if ($session) {
                $keysParam = $session->report_keys;
            }
        }

        $dataItemsArray = $dataItems ? array_map('trim', explode(',', $dataItems)) : [];

        $payload = $this->reportService->fetch($keysParam, $dataItemsArray, $id);

        return response()->json($payload);
    }

    public function init(Request $request)
    {
        $hashId = $this->initSessionInternal('general_information');
        return response()->json([
            'status' => 'success',
            'hash_id' => $hashId
        ]);
    }
}
