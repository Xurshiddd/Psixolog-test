<?php

namespace App\Http\Controllers;

use App\Models\Faculity;
use App\Models\Module;
use App\Models\ResultCategory;
use App\Models\SolveTest;
use App\Models\Test;
use App\Models\User;
use App\Services\StudentPopulationStatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function __construct(private StudentPopulationStatsService $studentPopulationStatsService)
    {
    }

    public function index(Request $request)
    {
        if (auth()->user()->role === 'student') {
            return Inertia::render("Student/Index", [
                'solvedTestsCount' => auth()->user()->usersTestsResults()->count(),
                'modulesCount' => Module::where('is_active', true)->count(),
            ]);
        }

        $totalInstitutionStudents = $this->studentPopulationStatsService->getBakalavrKunduzgiTotal();
        $activeModulesCount = Module::where('is_active', true)->count();
        $studentUsersQuery = User::query()->where('role', 'student');
        $studentUsersCount = (clone $studentUsersQuery)->count();
        $studentsSolvedAtLeastOneModule = (clone $studentUsersQuery)
            ->whereHas('usersTestsResults')
            ->count();
        $studentsWithAllModulesSolved = $activeModulesCount > 0
            ? (clone $studentUsersQuery)
                ->withCount([
                    'usersTestsResults as active_modules_solved_count' => fn ($query) => $query->where('modules.is_active', true),
                ])
                ->having('active_modules_solved_count', '>=', $activeModulesCount)
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
                    if ($val === null) continue;
                    if (!isset($valueCounts[$val])) $valueCounts[$val] = 0;
                    $valueCounts[$val]++;
                }

                if (empty($valueCounts)) continue;

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

    private function calculatePercentage(int $count, int $total): float
    {
        if ($total <= 0) {
            return 0;
        }

        return round(($count / $total) * 100, 2);
    }
}
