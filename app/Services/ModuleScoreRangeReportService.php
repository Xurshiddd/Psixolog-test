<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ModuleScoreRangeReportService
{
    private const CACHE_TTL_MINUTES = 10;

    public function paginate(int $moduleId, int $minScore, int $maxScore, int $perPage = 15): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();

        $payload = Cache::remember(
            $this->makeCacheKey($moduleId, $minScore, $maxScore, 'page', [
                'page' => $page,
                'per_page' => $perPage,
            ]),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            function () use ($moduleId, $minScore, $maxScore, $page, $perPage): array {
                $query = $this->filteredQuery($moduleId, $minScore, $maxScore);

                return [
                    'total' => DB::query()
                        ->fromSub($this->filteredQuery($moduleId, $minScore, $maxScore), 'module_score_report')
                        ->count(),
                    'items' => $query
                        ->forPage($page, $perPage)
                        ->get()
                        ->map(fn ($row) => (array) $row)
                        ->all(),
                ];
            }
        );

        return new Paginator(
            collect($payload['items'])->map(fn (array $row) => (object) $row),
            $payload['total'],
            $perPage,
            $page,
            [
                'path' => url()->current(),
                'pageName' => 'page',
                'query' => request()->query(),
            ]
        );
    }

    public function exportRows(int $moduleId, int $minScore, int $maxScore): Collection
    {
        $rows = Cache::remember(
            $this->makeCacheKey($moduleId, $minScore, $maxScore, 'export'),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn (): array => $this->filteredQuery($moduleId, $minScore, $maxScore)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all()
        );

        return collect($rows)->map(fn (array $row) => (object) $row);
    }

    private function filteredQuery(int $moduleId, int $minScore, int $maxScore)
    {
        return DB::query()
            ->fromSub($this->scoreTotalsQuery($moduleId), 'module_scores')
            ->join('users', 'users.id', '=', 'module_scores.user_id')
            ->leftJoin('faculities', 'faculities.id', '=', 'users.faculity_id')
            ->leftJoin('groups', 'groups.id', '=', 'users.group_id')
            ->where('users.role', 'student')
            ->whereBetween('module_scores.score', [$minScore, $maxScore])
            ->whereExists(function ($query) use ($moduleId) {
                $query->selectRaw('1')
                    ->from('users_tests_results')
                    ->whereColumn('users_tests_results.user_id', 'users.id')
                    ->where('users_tests_results.module_id', $moduleId);
            })
            ->orderByDesc('module_scores.score')
            ->orderBy('users.name')
            ->selectRaw("
                users.id,
                users.login,
                users.name,
                COALESCE(faculities.name, '-') as faculity_name,
                COALESCE(groups.name, '-') as group_name,
                COALESCE(users.level, '-') as level,
                module_scores.score
            ");
    }

    private function scoreTotalsQuery(int $moduleId)
    {
        return DB::table('solve_tests')
            ->join('test_options', 'test_options.id', '=', 'solve_tests.test_option_id')
            ->select('solve_tests.user_id', 'solve_tests.module_id')
            ->selectRaw('SUM(test_options.option_value) as score')
            ->where('solve_tests.module_id', $moduleId)
            ->whereNotNull('solve_tests.test_option_id')
            ->groupBy('solve_tests.user_id', 'solve_tests.module_id');
    }

    private function makeCacheKey(int $moduleId, int $minScore, int $maxScore, string $suffix, array $extra = []): string
    {
        return 'dashboard:module-score-range-report:'.$suffix.':'.md5(json_encode([
            'module_id' => $moduleId,
            'min_score' => $minScore,
            'max_score' => $maxScore,
            'extra' => $extra,
        ]));
    }
}
