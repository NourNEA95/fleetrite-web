<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GsLastEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function getEvents(Request $request)
    {
        $user = $request->user();
        $rows = $request->input('rows', 25);
        $search = $request->input('search');
        $sidx = $request->input('sidx', 'dt_tracker');
        $sord = $request->input('sord', 'desc');

        $query = DB::table('gs_user_last_events_data as e')
            ->leftJoin('gs_objects as o', 'e.imei', '=', 'o.imei')
            ->where('e.user_id', $user->id);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('e.event_desc', 'like', "%$search%")
                  ->orWhere('e.imei', 'like', "%$search%")
                  ->orWhere('o.name', 'like', "%$search%");
            });
        }

        $pagination = $query->select('e.*', 'o.name as object_name')
            ->orderBy('e.'.$sidx, $sord)
            ->paginate($rows);

        return response()->json(array_merge(
            ['ok' => true],
            $pagination->toArray()
        ));
    }

    public function getLatestEvents(Request $request)
    {
        $user = $request->user();
        $lastId = $request->input('last_id', 0);

        $events = GsLastEvent::where('user_id', $user->id)
            ->where('event_id', '>', $lastId)
            ->orderBy('event_id', 'desc')
            ->get();

        return response()->json([
            'ok' => true,
            'events' => $events
        ]);
    }

    public function clearEvents(Request $request)
    {
        $user = $request->user();
        GsLastEvent::where('user_id', $user->id)->delete();

        return response()->json(['ok' => true]);
    }
}
