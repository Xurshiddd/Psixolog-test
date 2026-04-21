<?php

namespace App\Application\Dashboard\Services;

use App\Application\Dashboard\Data\ModuleScoreReportFilters;
use App\Application\Dashboard\Queries\CountStudentLoginsQuery;
use App\Models\Module;
use App\Models\User;
use App\Services\DashboardAggregateCacheService;
use App\Services\ModuleScoreRangeReportService;
use App\Services\ResultCategoryStatsService;
use App\Services\StudentPopulationStatsService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BuildDashboardPage
{
    public function __construct(
        private StudentPopulationStatsService $studentPopulationStatsService,
        private ModuleScoreRangeReportService $moduleScoreRangeReportService,
        private ResultCategoryStatsService $resultCategoryStatsService,
        private DashboardAggregateCacheService $dashboardAggregateCacheService,
        private CountStudentLoginsQuery $countStudentLoginsQuery,
    ) {}

    /**
     * @return array{component: string, props: array<string, mixed>}
     */
    public function forUser(User $user, ModuleScoreReportFilters $filters): array
    {
        if ($user->role === 'student') {
            return [
                'component' => 'Student/Index',
                'props' => [
                    'solvedTestsCount' => $user->usersTestsResults()->count(),
                    'modulesCount' => Module::query()->where('is_active', true)->count(),
                ],
            ];
        }

        $overview = $this->dashboardAggregateCacheService->overview();
        $modules = $this->dashboardAggregateCacheService->moduleSummaries();
        $selectedModule = $filters->moduleId
            ? $modules->firstWhere('id', $filters->moduleId)
            : null;

        $totalInstitutionStudents = $this->studentPopulationStatsService->getBakalavrKunduzgiTotal();
        $studentsLoggedInCount = $this->countStudentLoginsQuery->execute();
        $moduleScoreReport = $this->moduleScoreReport($selectedModule, $filters);

        return [
            'component' => 'Dashboard',
            'props' => [
                'testsCount' => $overview['tests_count'],
                'modulesCount' => $overview['modules_count'],
                'modules' => $modules->map(fn (object $module): array => [
                    'id' => $module->id,
                    'name' => $module->name,
                ]),
                'studentPopulationStats' => [
                    'totalStudents' => $totalInstitutionStudents,
                    'platformStudentsCount' => $overview['student_users_count'],
                    'loggedInStudentsCount' => $studentsLoggedInCount,
                    'solvedAtLeastOneCount' => $overview['students_solved_at_least_one_module'],
                    'solvedAllModulesCount' => $overview['students_with_all_modules_solved'],
                    'loginPercentage' => $this->calculatePercentage($overview['student_users_count'], $totalInstitutionStudents),
                    'solvedAtLeastOnePercentage' => $this->calculatePercentage($overview['students_solved_at_least_one_module'], $totalInstitutionStudents),
                    'solvedAllModulesPercentage' => $this->calculatePercentage($overview['students_with_all_modules_solved'], $totalInstitutionStudents),
                ],
                'moduleStats' => $modules->map(fn (object $module): array => [
                    'name' => $module->name,
                    'solvedCount' => $module->users_tests_results_count,
                ]),
                'reportFilters' => [
                    ...$filters->toViewData(),
                    'module_id' => $selectedModule?->id,
                ],
                'moduleScoreReport' => $moduleScoreReport,
                'resultCategoryStats' => $this->resultCategoryStatsService->getStats(),
                'categoryStudentStats' => $this->dashboardAggregateCacheService->categoryStudentStats(),
                'faculityStudentStats' => $this->dashboardAggregateCacheService->faculityStudentStats(),
            ],
        ];
    }

    private function moduleScoreReport(?object $selectedModule, ModuleScoreReportFilters $filters): ?LengthAwarePaginator
    {
        if ($selectedModule === null || ! $filters->isReady()) {
            return null;
        }

        return $this->moduleScoreRangeReportService
            ->paginate($selectedModule->id, $filters->minScore, $filters->maxScore, 15)
            ->through(fn ($row): array => [
                'id' => (int) $row->id,
                'login' => (string) $row->login,
                'name' => $row->name,
                'faculity_name' => $row->faculity_name,
                'group_name' => $row->group_name,
                'level' => $row->level,
                'score' => (int) $row->score,
            ]);
    }

    private function calculatePercentage(int $count, int $total): float
    {
        if ($total <= 0) {
            return 0;
        }

        return round(($count / $total) * 100, 2);
    }
}
