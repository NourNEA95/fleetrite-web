<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\KocDirectorateReportService;
use App\Traits\HandlesModularReportSessions;
use Illuminate\Http\Request;

class KocDirectorateController extends Controller
{
    use HandlesModularReportSessions;

    protected $service;

    public function __construct(KocDirectorateReportService $service)
    {
        $this->service = $service;
    }

    /**
     * Start a modular report session.
     */
    public function init(Request $request)
    {
        return $this->initializeSession($request, 'overspeednew');
    }

    /**
     * Generate the report by calling legacy API.
     */
    public function generate(Request $request)
    {
        $hashId = $request->input('hash_id');
        $userId = $request->user()->id;

        $result = $this->service->generate($request->all(), $userId);

        if ($result['status'] === 'success' && !empty($result['keys'])) {
            $this->appendKeysInternal($hashId, $result['keys']);
            return response()->json($result);
        }

        return response()->json($result, 400);
    }

    /**
     * Fetch paginated report data.
     */
    public function fetch(Request $request)
    {
        $hashId = $request->input('hash_id');
        $session = $this->getValidSession($hashId);

        if (!$session || empty($session->report_keys)) {
            return response()->json(['error' => 'Session not found or not generated'], 404);
        }

        $keys = explode(',', $session->report_keys);
        
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('per_page', $request->input('limit', 10000));

        $data = $this->service->fetchByKeys($keys, $page, $limit);

        return response()->json($data);
    }
}
