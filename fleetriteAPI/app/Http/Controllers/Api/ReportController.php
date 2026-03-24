<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\GeneratedReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\ReportService;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::where('user_id', Auth::id());
        
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        
        $allowedFields = ['report_id', 'name', 'type', 'format'];
        if (in_array($sortBy, $allowedFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc');
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('name2', 'like', "%{$search}%")
                  ->orWhere('name3', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate($request->input('per_page', 10)));
    }

    public function store(Request $request)
    {
        $userId = Auth::id();

        // Convert data precisely for legacy compatibility match screenshot and legacy code
        $reportData = [
            'user_id' => $userId,
            'name' => (string) ($request->name ?? ''),
            'name2' => (string) ($request->name2 ?? ''),
            'name3' => (string) ($request->name3 ?? ''),
            'type' => (string) ($request->type ?? ''),
            'ignore_empty_reports' => $request->ignore_empty_reports ? 'true' : 'false',
            'format' => (string) ($request->format ?? ''),
            'show_coordinates' => $request->show_coordinates ? 'true' : 'false',
            'show_addresses' => $request->show_addresses ? 'true' : 'false',
            'markers_addresses' => $request->markers_addresses ? 'true' : 'false',
            'zones_addresses' => $request->zones_addresses ? 'true' : 'false',
            'stop_duration' => (int) ($request->stop_duration ?? 0),
            'speed_limit' => (int) ($request->speed_limit ?? 0),
            'imei' => is_array($request->imei) ? implode(',', $request->imei) : (string) ($request->imei ?? ''),
            'marker_ids' => is_array($request->marker_ids) ? implode(',', $request->marker_ids) : (string) ($request->marker_ids ?? ''),
            'zone_ids' => is_array($request->zone_ids) ? implode(',', $request->zone_ids) : (string) ($request->zone_ids ?? ''),
            'driver_ids' => is_array($request->driver_ids) ? implode(',', $request->driver_ids) : (string) ($request->driver_ids ?? ''),
            'sensor_names' => is_array($request->sensor_names) ? implode(',', $request->sensor_names) : (string) ($request->sensor_names ?? ''),
            'data_items' => is_array($request->data_items) ? implode(',', $request->data_items) : (string) ($request->data_items ?? ''),
            'other' => (is_array($request->other) && !empty($request->other)) ? json_encode($request->other) : '',
            'schedule_email_address' => (string) ($request->schedule_email_address ?? ''),
        ];

        // Handle schedule_period
        $period = '';
        if ($request->schedule_daily) $period .= 'd';
        if ($request->schedule_weekly) $period .= 'w';
        if ($request->schedule_monthly) $period .= 'm';
        $reportData['schedule_period'] = $period;

        // Disable strict mode so MySQL handles missing fields with its defaults (0000-00-00...)
        // exactly like the legacy PHP scripts do.
        DB::statement("SET SESSION sql_mode=''");

        $report = Report::updateOrCreate(
            ['report_id' => $request->id, 'user_id' => $userId],
            $reportData
        );

        return response()->json($report);
    }

    public function destroy($id)
    {
        $report = Report::where('user_id', Auth::id())->findOrFail($id);
        $report->delete();

        return response()->json(['message' => 'Report deleted successfully']);
    }

    public function getMetadata()
    {
        $userId = Auth::id();

        // Fetch Objects
        $objects = DB::table('gs_user_objects')
            ->join('gs_objects', 'gs_user_objects.imei', '=', 'gs_objects.imei')
            ->where('gs_user_objects.user_id', $userId)
            ->select('gs_objects.imei', 'gs_objects.name')
            ->get();

        // Fetch Zones
        $zones = DB::table('gs_user_zones')
            ->where('user_id', $userId)
            ->select('zone_id', 'zone_name')
            ->get();

        // Fetch Markers (Places)
        $markers = DB::table('gs_user_markers')->where('user_id', $userId)->get(['marker_id', 'marker_name']);
        $drivers = DB::table('gs_user_object_drivers')->where('user_id', $userId)->get(['driver_id', 'driver_name']);

        return response()->json([
            'objects' => $objects,
            'zones' => $zones,
            'markers' => $markers,
            'drivers' => $drivers
        ]);
    }

    public function getSensors(Request $request)
    {
        $imeis = $request->input('imeis', []);
        
        if (empty($imeis)) {
            return response()->json([]);
        }

        $sensors = DB::table('gs_object_sensors')
            ->whereIn('imei', $imeis)
            ->distinct()
            ->pluck('name');

        return response()->json($sensors);
    }

    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function generateReport(Request $request)
    {
        $params = $request->validate([
            'type' => 'required|string',
            'from' => 'required|string',
            'to' => 'required|string',
            'imei' => 'required',
            'stop_duration' => 'nullable|integer',
            'speed_limit' => 'nullable|integer',
            'key' => 'nullable|string',
            'sensor_names' => 'nullable',
            'marker_ids' => 'nullable',
            'zone_ids' => 'nullable',
            'driver_ids' => 'nullable',
            'data_items' => 'nullable',
            'show_coordinates' => 'nullable',
            'show_addresses' => 'nullable',
            'zones_addresses' => 'nullable',
            'markers_addresses' => 'nullable',
            'ignore_empty_reports' => 'nullable',
            'name' => 'nullable|string',
            'name2' => 'nullable|string',
            'name3' => 'nullable|string',
            'format' => 'nullable|string',
            'other_dn_starts_hour' => 'nullable|string',
            'other_dn_starts_minute' => 'nullable|string',
            'other_dn_ends_hour' => 'nullable|string',
            'other_dn_ends_minute' => 'nullable|string',
            'other_rag_low_score' => 'nullable|string',
            'other_rag_high_score' => 'nullable|string',
            'schedule_daily' => 'nullable|boolean',
            'schedule_weekly' => 'nullable|boolean',
            'schedule_monthly' => 'nullable|boolean',
            'schedule_email_address' => 'nullable|string',
        ]);

        // Fetch user settings for timezone conversion
        $userId = Auth::id() ?? 1;
        $userSettings = DB::table('gs_users')
            ->where('id', $userId)
            ->first(['timezone', 'dst', 'dst_start', 'dst_end']);
        
        $params['user_settings'] = [
            'timezone' => $userSettings->timezone ?? '+ 0 hour',
            'dst' => $userSettings->dst ?? 'false',
            'dst_start' => $userSettings->dst_start ?? '',
            'dst_end' => $userSettings->dst_end ?? '',
        ];

        if (!isset($params['key'])) {
            $params['key'] = md5(uniqid());
        }

        $result = $this->reportService->generate($params, Auth::id() ?? 1);

        if (($result['status'] ?? '') === 'error') {
            return response()->json($result, 400);
        }

        if (isset($result['data']) && $result['data'] === null) {
            // General information: only keys returned; data loaded via POST /reports/general-info/data
            return response()->json([
                'status' => 'success',
                'key' => $result['key'] ?? (($result['keys'][0] ?? '') ?: ''),
                'keys' => $result['keys'] ?? null,
                'ok' => $result['ok'] ?? true,
                'total' => $result['total'] ?? 1,
                'saved_ok' => $result['saved_ok'] ?? 1,
                'results' => $result['results'] ?? [],
                'data' => null,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'key' => $result['key'],
            'data' => $result['results'] ?? [],
            'debug' => $result['debug'] ?? [],
        ]);
    }

    /**
     * Paginated general information report data (table: general_information_reports2_api).
     * Body params: key or keys (required), page (default 1), per_page (default 1000), data_items (optional comma-separated).
     */
    public function fetchGeneralInfoData(Request $request)
    {
        $request->validate([
            'key' => 'nullable|string',
            'keys' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:1000',
            'data_items' => 'nullable|string',
        ]);

        $keysParam = $request->input('keys') ?: $request->input('key');
        if (!$keysParam) {
            return response()->json(['message' => 'Missing key(s) parameter'], 422);
        }

        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 1000);
        $dataItems = $request->input('data_items');
        $dataItemsArray = $dataItems ? array_map('trim', explode(',', $dataItems)) : [];

        $payload = $this->reportService->fetchGeneralInfoPaginated($keysParam, $page, $perPage, $dataItemsArray);

        return response()->json($payload);
    }

    public function indexGenerated(Request $request)
    {
        $query = GeneratedReport::where('user_id', Auth::id());
        
        $sortBy = $request->input('sort_by', 'dt_report');
        $sortOrder = $request->input('sort_order', 'desc');
        
        // Allowed sort fields for generated
        if (in_array($sortBy, ['dt_report', 'name', 'type', 'format'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('dt_report', 'desc');
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate($request->input('per_page', 10)));
    }

    public function destroyGenerated($id)
    {
        $report = GeneratedReport::where('user_id', Auth::id())->findOrFail($id);
        
        // In a real scenario, we might also delete the physical file from storage
        // but for now we follow the DB request
        $report->delete();

        return response()->json(['message' => 'Generated report deleted successfully']);
    }
}
