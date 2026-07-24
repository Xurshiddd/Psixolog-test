<?php

namespace App\Console\Commands;

use App\Jobs\SyncHemisEmployeesJob;
use App\Services\HemisEmployeeSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SyncHemisEmployees extends Command
{
    protected $signature = 'hemis:sync-employees {--per-page=200 : Har bir so\'rovdagi hodimlar soni}';

    protected $description = 'HEMIS\'dagi barcha xodimlarni platformaga sinxronlaydi (users + employees)';

    public function handle(HemisEmployeeSyncService $service): int
    {
        $this->info('HEMIS xodimlarini sinxronlash boshlandi...');
        $start = microtime(true);

        try {
            $result = $service->syncAll(
                perPage: (int) $this->option('per-page'),
                onProgress: function (int $page, int $seen, int $created, int $updated): void {
                    $this->line("  {$page}-sahifa: jami {$seen} (yangi {$created}, yangilandi {$updated})");
                },
            );
        } catch (Throwable $e) {
            Cache::put(SyncHemisEmployeesJob::CACHE_KEY, [
                'status' => 'failed',
                'finished_at' => now()->toDateTimeString(),
                'message' => $e->getMessage(),
            ], now()->addDay());

            $this->error('Xatolik: '.$e->getMessage());

            return self::FAILURE;
        }

        Cache::put(SyncHemisEmployeesJob::CACHE_KEY, [
            'status' => 'done',
            'finished_at' => now()->toDateTimeString(),
            'total' => $result['total'],
            'created' => $result['created'],
            'updated' => $result['updated'],
        ], now()->addDay());

        $elapsed = round(microtime(true) - $start, 1);

        $this->newLine();
        $this->info("Yakunlandi ({$elapsed}s): jami {$result['total']} — yangi {$result['created']}, yangilandi {$result['updated']}.");

        return self::SUCCESS;
    }
}
