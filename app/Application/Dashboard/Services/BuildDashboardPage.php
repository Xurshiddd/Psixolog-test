<?php

namespace App\Application\Dashboard\Services;

use App\Application\Dashboard\Data\ModuleScoreReportFilters;
use App\Application\Dashboard\Data\ReportAudience;
use App\Application\Dashboard\Queries\CountStudentLoginsQuery;
use App\Models\Module;
use App\Models\User;
use App\Services\DashboardAggregateCacheService;
use App\Services\EmployeePopulationStatsService;
use App\Services\ModuleScoreRangeReportService;
use App\Services\ResultCategoryStatsService;
use App\Services\StudentPopulationStatsService;
use App\Support\RiskFlag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BuildDashboardPage
{
    public function __construct(
        private StudentPopulationStatsService $studentPopulationStatsService,
        private ModuleScoreRangeReportService $moduleScoreRangeReportService,
        private ResultCategoryStatsService $resultCategoryStatsService,
        private DashboardAggregateCacheService $dashboardAggregateCacheService,
        private CountStudentLoginsQuery $countStudentLoginsQuery,
        private EmployeePopulationStatsService $employeePopulationStatsService,
    ) {}

    /**
     * @return array{component: string, props: array<string, mixed>}
     */
    public function forUser(User $user, ModuleScoreReportFilters $filters): array
    {
        if (in_array($user->role, ['student', 'employee', 'guest'], true)) {
            return [
                'component' => 'Student/Index',
                'props' => [
                    'solvedTestsCount' => $user->usersTestsResults()->count(),
                    'modulesCount' => Module::query()
                        ->where('is_active', true)
                        ->forAudience($user->role)
                        ->count(),
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
        $totalInstitutionEmployees = $this->employeePopulationStatsService->getTotalEmployees();
        $employeesLoggedInCount = $this->countStudentLoginsQuery->execute('employee');
        $guestsLoggedInCount = $this->countStudentLoginsQuery->execute('guest');
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
                'employeePopulationStats' => [
                    'totalEmployees' => $totalInstitutionEmployees,
                    'platformEmployeesCount' => $overview['employee_users_count'],
                    'loggedInEmployeesCount' => $employeesLoggedInCount,
                    'solvedAtLeastOneCount' => $overview['employees_solved_at_least_one_module'],
                    'solvedAllModulesCount' => $overview['employees_with_all_modules_solved'],
                    'loginPercentage' => $this->calculatePercentage($overview['employee_users_count'], $totalInstitutionEmployees),
                    'solvedAtLeastOnePercentage' => $this->calculatePercentage($overview['employees_solved_at_least_one_module'], $totalInstitutionEmployees),
                    'solvedAllModulesPercentage' => $this->calculatePercentage($overview['employees_with_all_modules_solved'], $totalInstitutionEmployees),
                ],
                'guestPopulationStats' => [
                    'platformGuestsCount' => $overview['guest_users_count'],
                    'loggedInGuestsCount' => $guestsLoggedInCount,
                    'solvedAtLeastOneCount' => $overview['guests_solved_at_least_one_module'],
                    'solvedAllModulesCount' => $overview['guests_with_all_modules_solved'],
                    'solvedAtLeastOnePercentage' => $this->calculatePercentage($overview['guests_solved_at_least_one_module'], $overview['guest_users_count']),
                    'solvedAllModulesPercentage' => $this->calculatePercentage($overview['guests_with_all_modules_solved'], $overview['guest_users_count']),
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
                'reportAudienceStats' => $this->moduleScoreReportAudienceStats($selectedModule, $filters),
                'resultCategoryStats' => $this->resultCategoryStatsService->getStats(),
                'categoryStudentStats' => $this->dashboardAggregateCacheService->categoryStudentStats(),
                'flagStudentStats' => $this->dashboardAggregateCacheService->flagStudentStats(),
                'flagOptions' => RiskFlag::options(),
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
                'login' => blank($row->login) ? '-' : (string) $row->login,
                'name' => $row->name,
                'role' => (string) $row->role,
                'role_label' => ReportAudience::label($row->role),
                'faculity_name' => $row->faculity_name,
                'group_name' => $row->group_name,
                'level' => $row->level,
                'flag' => RiskFlag::isValid($row->flag) ? $row->flag : null,
                'score' => (int) $row->score,
            ]);
    }

    /**
     * Tanlangan oraliqdagi natijalarni toifalar bo'yicha sanaydi — avtomatik
     * xulosa har bir toifa uchun alohida yoziladi.
     *
     * @return list<array{role: string, label: string, count: int}>
     */
    private function moduleScoreReportAudienceStats(?object $selectedModule, ModuleScoreReportFilters $filters): array
    {
        if ($selectedModule === null || ! $filters->isReady()) {
            return [];
        }

        $counts = $this->moduleScoreRangeReportService->roleCounts(
            $selectedModule->id,
            $filters->minScore,
            $filters->maxScore,
        );

        $stats = [];

        foreach ($counts as $role => $count) {
            $stats[] = [
                'role' => $role,
                'label' => ReportAudience::label($role),
                'count' => $count,
            ];
        }

        return $stats;
    }

    private function calculatePercentage(int $count, int $total): float
    {
        if ($total <= 0) {
            return 0;
        }

        return round(($count / $total) * 100, 2);
    }
}
