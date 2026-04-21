<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Faculity;
use App\Models\Module;
use App\Models\Test;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardAggregateCacheService
{
    private const CACHE_TTL_MINUTES = 10;

    private const OVERVIEW_KEY = 'dashboard:overview';

    private const MODULE_SUMMARIES_KEY = 'dashboard:module-summaries';

    private const CATEGORY_STUDENT_STATS_KEY = 'dashboard:category-student-stats';

    private const FACULITY_STUDENT_STATS_KEY = 'dashboard:faculity-student-stats';

    /**
     * @return array<string, int>
     */
    public function overview(): array
    {
        /** @var array<string, int> */
        return Cache::remember(
            self::OVERVIEW_KEY,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            function (): array {
                $activeModulesCount = Module::query()->where('is_active', true)->count();
                $studentUsersQuery = User::query()->where('role', 'student');
                $studentUsersCount = (clone $studentUsersQuery)->count();
                $studentsSolvedAtLeastOneModule = (clone $studentUsersQuery)
                    ->whereHas('usersTestsResults')
                    ->count();

                $studentsWithAllModulesSolved = $activeModulesCount > 0
                    ? DB::query()
                        ->fromSub(
                            (clone $studentUsersQuery)->withCount([
                                'usersTestsResults as active_modules_solved_count' => fn ($query) => $query->where('modules.is_active', true),
                            ]),
                            'student_module_counts'
                        )
                        ->where('active_modules_solved_count', '>=', $activeModulesCount)
                        ->count()
                    : 0;

                return [
                    'tests_count' => Test::query()->count(),
                    'modules_count' => Module::query()->count(),
                    'active_modules_count' => $activeModulesCount,
                    'student_users_count' => $studentUsersCount,
                    'students_solved_at_least_one_module' => $studentsSolvedAtLeastOneModule,
                    'students_with_all_modules_solved' => $studentsWithAllModulesSolved,
                ];
            }
        );
    }

    public function moduleSummaries(): Collection
    {
        return collect(
            Cache::remember(
                self::MODULE_SUMMARIES_KEY,
                now()->addMinutes(self::CACHE_TTL_MINUTES),
                function (): array {
                    return Module::query()
                        ->select(['id', 'name'])
                        ->withCount('usersTestsResults')
                        ->orderBy('name')
                        ->get()
                        ->map(fn (Module $module): array => [
                            'id' => (int) $module->id,
                            'name' => (string) $module->name,
                            'users_tests_results_count' => (int) $module->users_tests_results_count,
                        ])
                        ->all();
                }
            )
        )->map(fn (array $item): object => (object) $item);
    }

    public function categoryStudentStats(): Collection
    {
        return collect(
            Cache::remember(
                self::CATEGORY_STUDENT_STATS_KEY,
                now()->addMinutes(self::CACHE_TTL_MINUTES),
                function (): array {
                    return Category::query()
                        ->select(['id', 'name'])
                        ->withCount('usersCategory')
                        ->orderBy('name')
                        ->get()
                        ->map(fn (Category $category): array => [
                            'name' => (string) $category->name,
                            'studentCount' => (int) $category->users_category_count,
                        ])
                        ->all();
                }
            )
        );
    }

    public function faculityStudentStats(): Collection
    {
        return collect(
            Cache::remember(
                self::FACULITY_STUDENT_STATS_KEY,
                now()->addMinutes(self::CACHE_TTL_MINUTES),
                function (): array {
                    return Faculity::query()
                        ->select(['id', 'name'])
                        ->withCount([
                            'users as student_count' => fn ($query) => $query->where('role', 'student'),
                        ])
                        ->orderByDesc('student_count')
                        ->get()
                        ->map(fn (Faculity $faculity): array => [
                            'name' => (string) $faculity->name,
                            'studentCount' => (int) $faculity->student_count,
                        ])
                        ->all();
                }
            )
        );
    }

    public function forgetAll(): void
    {
        foreach ($this->allKeys() as $key) {
            Cache::forget($key);
        }
    }

    /**
     * @return list<string>
     */
    public function allKeys(): array
    {
        return [
            self::OVERVIEW_KEY,
            self::MODULE_SUMMARIES_KEY,
            self::CATEGORY_STUDENT_STATS_KEY,
            self::FACULITY_STUDENT_STATS_KEY,
        ];
    }
}
