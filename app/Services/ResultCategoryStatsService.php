<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ResultCategoryStatsService
{
    public function getStats(): Collection
    {
        return collect(Cache::remember(
            $this->cacheKey(),
            now()->addMinutes(10),
            fn (): array => DB::query()
                ->fromSub($this->topValuePerCompletedModuleQuery(), 'top_values')
                ->join('result_categories', function ($join) {
                    $join->on('result_categories.module_id', '=', 'top_values.module_id')
                        ->on('result_categories.value', '=', 'top_values.option_value');
                })
                ->where('top_values.row_num', 1)
                ->groupBy('result_categories.id', 'result_categories.name')
                ->orderBy('result_categories.id')
                ->select('result_categories.name')
                ->selectRaw('COUNT(*) as solved_count')
                ->get()
                ->map(function (object $row): array {
                    return [
                        'name' => $row->name,
                        'solvedCount' => (int) $row->solved_count,
                    ];
                })
                ->all()
        ));
    }

    public function flush(): void
    {
        Cache::forever($this->versionKey(), $this->cacheVersion() + 1);
    }

    private function topValuePerCompletedModuleQuery()
    {
        $optionCounts = DB::table('solve_tests')
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'solve_tests.user_id')
                    ->where('users.role', '=', 'student');
            })
            ->join('users_tests_results', function ($join) {
                $join->on('users_tests_results.user_id', '=', 'solve_tests.user_id')
                    ->on('users_tests_results.module_id', '=', 'solve_tests.module_id');
            })
            ->join('test_options', 'test_options.id', '=', 'solve_tests.test_option_id')
            ->groupBy('solve_tests.user_id', 'solve_tests.module_id', 'test_options.option_value')
            ->select([
                'solve_tests.user_id',
                'solve_tests.module_id',
                'test_options.option_value',
            ])
            ->selectRaw('COUNT(*) as option_count');

        return DB::query()
            ->fromSub($optionCounts, 'option_counts')
            ->select([
                'option_counts.user_id',
                'option_counts.module_id',
                'option_counts.option_value',
            ])
            ->selectRaw(
                'ROW_NUMBER() OVER (
                    PARTITION BY option_counts.user_id, option_counts.module_id
                    ORDER BY option_counts.option_count DESC, option_counts.option_value ASC
                ) as row_num'
            );
    }

    private function cacheKey(): string
    {
        return 'dashboard:result-category-stats:v'.$this->cacheVersion();
    }

    private function cacheVersion(): int
    {
        return (int) Cache::get($this->versionKey(), 1);
    }

    private function versionKey(): string
    {
        return 'dashboard:result-category-stats:version';
    }
}
