<?php

namespace App\Traits;

use App\Models\ModularReportSession;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
            Log::info("ModularSession Append: hash=$hashId, user=$userId, vehicles=$count");

            $existingStr = trim($session->report_keys ?? '');
            $existingKeys = $existingStr ? explode(',', $existingStr) : [];
            
            $allKeys = array_unique(array_merge($existingKeys, $keys));
            $allKeys = array_filter($allKeys, function($val) {
                return !empty(trim($val));
            });
            
            $session->update([
                'report_keys' => implode(',', $allKeys)
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("ModularReportSession Append Error: " . $e->getMessage());
            return false;
        }
    }
}
