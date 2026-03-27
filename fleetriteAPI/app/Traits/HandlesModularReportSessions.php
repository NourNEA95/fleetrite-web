<?php

namespace App\Traits;

use App\Models\ModularReportSession;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

trait HandlesModularReportSessions
{
    public function initSessionInternal(string $reportType)
    {
        try {
            $hashId = Str::random(32);
            $userId = Auth::id() ?? 1;
            
            Log::info("ModularSession Init: type=$reportType, user=$userId, hash=$hashId");

            ModularReportSession::create([
                'hash_id' => $hashId,
                'user_id' => $userId,
                'report_type' => $reportType,
                'report_keys' => '',
                'status' => 'collecting',
                'dt_created' => now()
            ]);

            return $hashId;
        } catch (\Exception $e) {
            Log::error("ModularReportSession Init Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function appendKeysInternal(string $hashId, array $keys)
    {
        try {
            $userId = Auth::id() ?? 1;
            $session = ModularReportSession::where('hash_id', $hashId)
                ->where('user_id', $userId)
                ->first();

            if (!$session) {
                Log::warning("ModularSession Append Fail: Session not found for hash=$hashId, user=$userId");
                return false;
            }

            $count = count($keys);
            Log::info("ModularSession Append: hash=$hashId, user=$userId, keys=$count");

            if ($count === 0) return true;

            $keysStr = implode(',', $keys);
            
            // Use atomic update to prevent race conditions during parallel batch generation
            ModularReportSession::where('hash_id', $hashId)
                ->where('user_id', $userId)
                ->update([
                    'report_keys' => DB::raw("IF(report_keys = '' OR report_keys IS NULL, '$keysStr', CONCAT(report_keys, ',', '$keysStr'))")
                ]);

            return true;
        } catch (\Exception $e) {
            Log::error("ModularReportSession Append Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper to initialize a session from a request.
     */
    public function initializeSession(Request $request, string $reportType)
    {
        try {
            $hashId = $this->initSessionInternal($reportType);
            return response()->json([
                'status' => 'success',
                'hash_id' => $hashId
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper to append keys to a session from a request.
     * Note: This version is a simple wrapper for appendKeysInternal.
     */
    public function appendToSession(Request $request, array $keys)
    {
        $hashId = $request->input('hash_id');
        if (!$hashId) {
            return response()->json(['error' => 'No hash_id provided'], 400);
        }

        $success = $this->appendKeysInternal($hashId, $keys);
        
        return response()->json([
            'status' => $success ? 'success' : 'error',
            'keys' => $keys
        ], $success ? 200 : 500);
    }

    /**
     * Get a valid session and validate ownership.
     */
    public function getValidSession($hashId)
    {
        if (!$hashId) return null;
        
        $userId = Auth::id() ?? 1;
        return ModularReportSession::where('hash_id', $hashId)
            ->where('user_id', $userId)
            ->first();
    }
}
