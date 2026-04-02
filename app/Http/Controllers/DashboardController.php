<?php

namespace App\Http\Controllers;

use App\Exports\ModuleScoreRangeExport;
use App\Models\Faculity;
use App\Models\Module;
use App\Models\ResultCategory;
use App\Models\SolveTest;
use App\Models\Test;
use App\Models\User;
use App\Services\ModuleScoreRangeReportService;
use App\Services\StudentPopulationStatsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function __construct(
        private StudentPopulationStatsService $studentPopulationStatsService,
        private ModuleScoreRangeReportService $moduleScoreRangeReportService,
    ) {}

    public function index(Request $request)
    {
        if (auth()->user()->role === 'student') {
            return Inertia::render('Student/Index', [
                'solvedTestsCount' => auth()->user()->usersTestsResults()->count(),
                'modulesCount' => Module::where('is_active', true)->count(),
            ]);
        }

        $reportFilters = $this->parseReportFilters($request);
        $totalInstitutionStudents = $this->studentPopulationStatsService->getBakalavrKunduzgiTotal();
        $activeModulesCount = Module::where('is_active', true)->count();
        $studentUsersQuery = User::query()->where('role', 'student');
        $studentUsersCount = (clone $studentUsersQuery)->count();
        $studentsSolvedAtLeastOneModule = (clone $studentUsersQuery)
            ->whereHas('usersTestsResults')
            ->count();
        $studentsWithAllModulesSolved = $activeModulesCount > 0
            ? \Illuminate\Support\Facades\DB::query()
                ->fromSub(
                    (clone $studentUsersQuery)->withCount([
                        'usersTestsResults as active_modules_solved_count' => fn ($query) => $query->where('modules.is_active', true),
                    ]),
                    'student_module_counts'
                )
                ->where('active_modules_solved_count', '>=', $activeModulesCount)
                ->count()
            : 0;
        $studentsLoggedInCount = Activity::query()
            ->where('log_name', 'auth')
            ->where('event', 'login')
            ->where('causer_type', User::class)
            ->whereIn('causer_id', (clone $studentUsersQuery)->select('id'))
            ->distinct('causer_id')
            ->count('causer_id');

        $modules = Module::withCount('usersTestsResults')->orderBy('name')->get();
        $selectedModule = $reportFilters['module_id']
            ? $modules->firstWhere('id', $reportFilters['module_id'])
            : null;

        $isReportReady = $selectedModule !== null
            && $reportFilters['min_score'] !== null
            && $reportFilters['max_score'] !== null
            && $reportFilters['min_score'] <= $reportFilters['max_score'];

        $moduleScoreReport = $isReportReady
            ? $this->moduleScoreRangeReportService
                ->paginate(
                    $selectedModule->id,
                    $reportFilters['min_score'],
                    $reportFilters['max_score'],
                    15
                )
                ->through(fn ($row) => [
                    'id' => (int) $row->id,
                    'login' => (string) $row->login,
                    'name' => $row->name,
                    'faculity_name' => $row->faculity_name,
                    'group_name' => $row->group_name,
                    'level' => $row->level,
                    'score' => (int) $row->score,
                ])
            : null;

        $categoryCounts = [];

        $students = (clone $studentUsersQuery)->get();
        foreach ($students as $student) {
            $completedModuleIds = $student->usersTestsResults()->pluck('module_id')->toArray();

            foreach ($completedModuleIds as $moduleId) {
                $answers = SolveTest::where('user_id', $student->id)
                    ->where('module_id', $moduleId)
                    ->with('testOption')
                    ->get();

                if ($answers->isEmpty()) {
                    continue;
                }

                $valueCounts = [];
                foreach ($answers as $ans) {
                    $val = $ans->testOption->option_value ?? null;
                    if ($val === null) {
                        continue;
                    }
                    if (! isset($valueCounts[$val])) {
                        $valueCounts[$val] = 0;
                    }
                    $valueCounts[$val]++;
                }

                if (empty($valueCounts)) {
                    continue;
                }

                arsort($valueCounts);
                $topValue = array_key_first($valueCounts);

                $category = ResultCategory::where('module_id', $moduleId)
                    ->where('value', $topValue)
                    ->first();

                if ($category) {
                    $categoryCounts[$category->name] = ($categoryCounts[$category->name] ?? 0) + 1;
                }
            }
        }

        $categoryStatData = collect($categoryCounts)->map(function ($count, $name) {
            return [
                'name' => $name,
                'solvedCount' => $count,
            ];
        })->values();

        return Inertia::render('Dashboard', [
            'testsCount' => Test::count(),
            'modulesCount' => Module::count(),
            'modules' => $modules->map(fn ($module) => [
                'id' => $module->id,
                'name' => $module->name,
            ]),
            'studentPopulationStats' => [
                'totalStudents' => $totalInstitutionStudents,
                'platformStudentsCount' => $studentUsersCount,
                'loggedInStudentsCount' => $studentsLoggedInCount,
                'solvedAtLeastOneCount' => $studentsSolvedAtLeastOneModule,
                'solvedAllModulesCount' => $studentsWithAllModulesSolved,
                'loginPercentage' => $this->calculatePercentage($studentUsersCount, $totalInstitutionStudents),
                'solvedAtLeastOnePercentage' => $this->calculatePercentage($studentsSolvedAtLeastOneModule, $totalInstitutionStudents),
                'solvedAllModulesPercentage' => $this->calculatePercentage($studentsWithAllModulesSolved, $totalInstitutionStudents),
            ],
            'moduleStats' => $modules->map(function ($module) {
                return [
                    'name' => $module->name,
                    'solvedCount' => $module->users_tests_results_count,
                ];
            }),
            'reportFilters' => [
                'module_id' => $selectedModule?->id,
                'min_score' => $reportFilters['min_score'],
                'max_score' => $reportFilters['max_score'],
                'is_ready' => $isReportReady,
            ],
            'moduleScoreReport' => $moduleScoreReport,
            'resultCategoryStats' => $categoryStatData,
            'categoryStudentStats' => \App\Models\Category::withCount('usersCategory')->get()->map(function ($cat) {
                return [
                    'name' => $cat->name,
                    'studentCount' => $cat->users_category_count,
                ];
            }),
            'faculityStudentStats' => Faculity::query()
                ->withCount([
                    'users as student_count' => fn ($query) => $query->where('role', 'student'),
                ])
                ->orderByDesc('student_count')
                ->get()
                ->map(function ($faculity) {
                    return [
                        'name' => $faculity->name,
                        'studentCount' => $faculity->student_count,
                    ];
                }),
        ]);
    }

    public function exportModuleScoreReport(Request $request)
    {
        $this->ensureModuleScoreReportAccess();

        $validated = $request->validate([
            'report_module_id' => ['required', 'integer', 'exists:modules,id'],
            'min_score' => ['required', 'integer', 'min:0'],
            'max_score' => ['required', 'integer', 'min:0'],
        ]);

        abort_if(
            (int) $validated['min_score'] > (int) $validated['max_score'],
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Ball oralig\'i noto\'g\'ri.'
        );

        $module = Module::findOrFail((int) $validated['report_module_id']);
        $rows = $this->moduleScoreRangeReportService->exportRows(
            $module->id,
            (int) $validated['min_score'],
            (int) $validated['max_score'],
        );

        $timestamp = now()->format('Y-m-d_H-i-s');
        $fileName = sprintf(
            'module_ballari_%s_%s.xlsx',
            Str::slug($module->name),
            $timestamp
        );

        return Excel::download(new ModuleScoreRangeExport($rows), $fileName);
    }

    private function calculatePercentage(int $count, int $total): float
    {
        if ($total <= 0) {
            return 0;
        }

        return round(($count / $total) * 100, 2);
    }

    private function parseReportFilters(Request $request): array
    {
        return [
            'module_id' => $this->parsePositiveInt($request->input('report_module_id')),
            'min_score' => $this->parseNonNegativeInt($request->input('min_score')),
            'max_score' => $this->parseNonNegativeInt($request->input('max_score')),
        ];
    }

    private function parsePositiveInt(mixed $value): ?int
    {
        $intValue = filter_var($value, FILTER_VALIDATE_INT);

        if ($intValue === false || $intValue <= 0) {
            return null;
        }

        return (int) $intValue;
    }

    private function parseNonNegativeInt(mixed $value): ?int
    {
        $intValue = filter_var($value, FILTER_VALIDATE_INT);

        if ($intValue === false || $intValue < 0) {
            return null;
        }

        return (int) $intValue;
    }

    private function ensureModuleScoreReportAccess(): void
    {
        abort_if(
            ! auth()->check() || auth()->user()->role === 'student',
            Response::HTTP_FORBIDDEN
        );
    }
}
