<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\TravelSheetService;
use App\Traits\HandlesModularReportSessions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TravelSheetController extends Controller
{
    use HandlesModularReportSessions;

    protected $travelSheetService;

    public function __construct(TravelSheetService $travelSheetService)
    {
        $this->travelSheetService = $travelSheetService;
    }

    /**
     * Initialize a modular report session.
     */
    public function init(Request $request)
    {
        return $this->initializeSession($request, 'travel_sheet');
    }

    /**
     * Generate a batch of the report.
     */
    public function generate(Request $request)
    {
        $params = $request->all();
        $userId = Auth::id() ?? 1;

        $result = $this->travelSheetService->generate($params, $userId);

        if ($result['status'] === 'success' && !empty($result['keys'])) {
            // Append to session if hash_id is provided
            if ($request->has('hash_id')) {
                $this->appendKeysInternal($request->hash_id, $result['keys']);
            }
        }

        return response()->json($result);
    }

    /**
     * Fetch the aggregated report data.
     */
    public function fetch(Request $request)
    {
        $hashId = $request->input('hash_id');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10000);

        try {
            $session = $this->getValidSession($hashId);
            if (!$session) {
                return response()->json(['error' => 'Invalid or expired report session'], 404);
            }

            $keys = $session->report_keys ? explode(',', $session->report_keys) : [];
            
            if (empty($keys)) {
                return response()->json([
                    'data' => [],
                    'total' => 0,
                    'message' => 'No data generated yet'
                ]);
            }

            $data = $this->travelSheetService->fetchByKeys($keys, $page, $perPage);

            return response()->json($data);

        } catch (\Exception $e) {
            Log::error("TravelSheet Fetch Error: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch report data'], 500);
        }
    }
}
