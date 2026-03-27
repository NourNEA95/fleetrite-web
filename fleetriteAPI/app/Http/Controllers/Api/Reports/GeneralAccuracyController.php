<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Models\GeneratedReport;
use App\Models\ModularReportSession;
use App\Services\Reports\GeneralAccuracyService;
use App\Traits\HandlesModularReportSessions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GeneralAccuracyController extends Controller
{
    use HandlesModularReportSessions;

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

        if ($result['status'] === 'success' && !empty($result['keys'])) {
            $generatedReport = GeneratedReport::create([
                'user_id' => $params['user_id'],
                'dt_report' => now(),
                'name' => $request->input('name') ?: 'General Accuracy',
                'type' => 'general_accuracy',
                'format' => $request->input('format', 'html'),
                'front_keys' => implode(',', $result['keys']),
                'objects' => is_array($request->input('imei')) ? count($request->input('imei')) : (empty($request->input('imei')) ? 0 : 1),
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

            $result['report_id'] = $generatedReport->report_id;
        }

        return response()->json($result);
    }

    /**
     * Fetch report data by keys.
     */
    public function fetch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'keys' => 'nullable|string',
            'id' => 'nullable|integer',
            'hash_id' => 'nullable|string',
            'page' => 'nullable|integer',
            'per_page' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $keysParam = $request->input('keys');
        $id = $request->input('id');
        $hashId = $request->input('hash_id');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 1000);

        // Priority: hash_id > id > keys
        if ($hashId) {
            $session = ModularReportSession::where('hash_id', $hashId)
                ->where('user_id', Auth::id() ?? 1)
                ->first();
            if ($session) {
                $keysParam = $session->report_keys;
            }
        }

        if (!$keysParam && !$id) {
            return response()->json(['status' => 'error', 'message' => 'The keys, id or hash_id is required.'], 422);
        }

        $result = $this->reportService->fetchData($keysParam, $page, $perPage, $id);
        
        return response()->json([
            'status' => 'success',
            'data' => $result['data'] ?? [],
            'totals' => $result['totals'] ?? []
        ]);
    }

    public function init(Request $request)
    {
        $hashId = $this->initSessionInternal('general_accuracy');
        return response()->json([
            'status' => 'success',
            'hash_id' => $hashId
        ]);
    }
}
