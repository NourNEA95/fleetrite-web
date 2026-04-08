<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\TravelSheetDayNightService;
use App\Traits\HandlesModularReportSessions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TravelSheetDayNightController extends Controller
{
    use HandlesModularReportSessions;

    protected $travelSheetDNService;

    public function __construct(TravelSheetDayNightService $travelSheetDNService)
    {
        $this->travelSheetDNService = $travelSheetDNService;
    }

    /**
     * Initialize a modular report session.
     */
    public function init(Request $request)
    {
        return $this->initializeSession($request, 'travel_sheet_dn');
    }

    /**
     * Generate a batch of the report.
     */
    public function generate(Request $request)
    {
        $params = $request->all();
        $userId = Auth::id() ?? 1;

        $result = $this->travelSheetDNService->generate($params, $userId);

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
        $perPage = $request->input('per_page', 100); // Default to 100 as per JS code typically

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

            $data = $this->travelSheetDNService->fetchByKeys($keys, $page, $perPage);

            return response()->json($data);

        } catch (\Exception $e) {
            Log::error("TravelSheetDayNight Fetch Error: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch report data'], 500);
        }
    }
}
