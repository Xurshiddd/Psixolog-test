<?php

namespace App\Console\Commands;

use App\Models\Module;
use App\Services\DashboardAggregateCacheService;
use Illuminate\Console\Command;

class SetModulesStudentAudienceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:audience-student';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Barcha modullarni faqat student auditoriyasi uchun belgilaydi (audiences = ["student"])';

    public function handle(DashboardAggregateCacheService $dashboardAggregateCacheService): int
    {
        $updated = Module::query()->update([
            'audiences' => json_encode(['student']),
        ]);

        $dashboardAggregateCacheService->forgetAll();

        $this->info("✅ {$updated} ta modul student uchun belgilandi.");

        return self::SUCCESS;
    }
}
