<?php

namespace App\Jobs;

use App\Services\HemisEmployeeSyncService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncHemisEmployeesJob
{
    use Dispatchable;

    public const CACHE_KEY = 'hemis:employee-sync';

    /**
     * HEMIS xodimlarini sinxronlaydi va holatni keshda saqlaydi.
     * `dispatchAfterResponse()` bilan chaqirilganda javob yuborilgandan keyin
     * shu jarayon ishlaydi — queue worker shart emas, so'rov timeout bo'lmaydi.
     */
    public function handle(HemisEmployeeSyncService $service): void
    {
        try {
            $result = $service->syncAll();

            Cache::put(self::CACHE_KEY, [
                'status' => 'done',
                'finished_at' => now()->toDateTimeString(),
                'total' => $result['total'],
                'created' => $result['created'],
                'updated' => $result['updated'],
            ], now()->addDay());
        } catch (Throwable $e) {
            Log::error('HEMIS xodim sinxron xatosi', ['message' => $e->getMessage()]);

            Cache::put(self::CACHE_KEY, [
                'status' => 'failed',
                'finished_at' => now()->toDateTimeString(),
                'message' => $e->getMessage(),
            ], now()->addDay());
        }
    }
}
