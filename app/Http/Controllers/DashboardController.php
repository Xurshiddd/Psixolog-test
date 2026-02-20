<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\SolveTest;
use App\Models\ResultCategory;
use App\Models\User;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->user()->role === 'student') {
            return Inertia::render("Student/Index", [
                'solvedTestsCount' => auth()->user()->usersTestsResults()->count(),
                'modulesCount' => Module::where('is_active', true)->count()
            ]);
        }

        $modules = Module::withCount('usersTestsResults')->orderBy('name')->get();

        $categoryCounts = [];

        $students = User::where('role', 'student')->get();
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
            'moduleStats' => $modules->map(function ($module) {
                return [
                    'name' => $module->name,
                    'solvedCount' => $module->users_tests_results_count
                ];
            }),
            'resultCategoryStats' => $categoryStatData
        ]);
    }
}
