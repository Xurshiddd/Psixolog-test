<?php

namespace App\Application\AdminStudents\Services;

use App\Models\User;
use App\Services\DashboardAggregateCacheService;
use App\Services\ModuleScoreRangeReportService;
use App\Services\ResultCategoryStatsService;
use Illuminate\Support\Facades\DB;

class AdminStudentRecordService
{
    public function __construct(
        private DashboardAggregateCacheService $dashboardAggregateCacheService,
        private ResultCategoryStatsService $resultCategoryStatsService,
        private ModuleScoreRangeReportService $moduleScoreRangeReportService,
    ) {}

    /**
     * @param  list<int>  $categoryIds
     */
    public function syncCategories(User $user, array $categoryIds): void
    {
        $user->usersCategory()->sync($categoryIds);
        $this->dashboardAggregateCacheService->forgetAll();
    }

    public function destroyResult(User $student, int $moduleId): void
    {
        DB::transaction(function () use ($student, $moduleId): void {
            DB::table('solve_tests')
                ->where('user_id', $student->id)
                ->where('module_id', $moduleId)
                ->delete();

            DB::table('users_tests_results')
                ->where('user_id', $student->id)
                ->where('module_id', $moduleId)
                ->delete();
        });

        $this->flushDashboardCaches();
    }

    private function flushDashboardCaches(): void
    {
        $this->dashboardAggregateCacheService->forgetAll();
        $this->resultCategoryStatsService->flush();
        $this->moduleScoreRangeReportService->flush();
    }
}
