<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\GeneralMergedService;
use App\Models\GeneratedReport;
use App\Models\ModularReportSession;
use App\Traits\HandlesModularReportSessions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GeneralMergedController extends Controller
{
    use HandlesModularReportSessions;

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
        $params['user_id'] = auth()->id() ?? 1;

        $result = $this->service->generate($params);

        if ($result['status'] === 'success' && !empty($result['keys'])) {
            // Save to generated reports for cleaner URL
            $generatedReport = GeneratedReport::create([
                'user_id' => $params['user_id'],
                'dt_report' => now(),
                'name' => $request->input('name') ?: 'General Information (Merged)',
                'type' => 'general_merged',
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
     * Fetch report data
     */
    public function fetch(Request $request)
    {
        $keys = $request->input('keys');
        $id = $request->input('id');
        $hashId = $request->input('hash_id');
        $page = (int) $request->input('page', 1);
        $rows = (int) $request->input('rows', 50);

        // Priority: hash_id > id > keys
        if ($hashId) {
            $session = ModularReportSession::where('hash_id', $hashId)
                ->where('user_id', Auth::id() ?? 1)
                ->first();
            if ($session) {
                $keys = $session->report_keys;
            }
        }

        if (empty($keys) && empty($id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No keys, id or hash_id provided'
            ], 400);
        }

        $result = $this->service->fetchData($keys, $page, $rows, $id);
        return response()->json($result);
    }

    public function init(Request $request)
    {
        $hashId = $this->initSessionInternal('general_merged');
        return response()->json([
            'status' => 'success',
            'hash_id' => $hashId
        ]);
    }
}
