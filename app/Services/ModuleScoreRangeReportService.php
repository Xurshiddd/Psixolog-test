<?php

namespace App\Services;

use App\Application\Dashboard\Data\ReportAudience;
use App\Support\RiskFlag;
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

    /**
     * Tanlangan ball oralig'idagi foydalanuvchilarni rollar bo'yicha sanaydi.
     *
     * @return array<string, int>
     */
    public function roleCounts(int $moduleId, int $minScore, int $maxScore): array
    {
        $counts = Cache::remember(
            $this->makeCacheKey($moduleId, $minScore, $maxScore, 'role-counts'),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn (): array => DB::query()
                ->fromSub($this->filteredQuery($moduleId, $minScore, $maxScore), 'module_score_report')
                ->select('role')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('role')
                ->pluck('total', 'role')
                ->all()
        );

        $roleCounts = [];

        foreach (ReportAudience::roles() as $role) {
            $roleCounts[$role] = (int) ($counts[$role] ?? 0);
        }

        return $roleCounts;
    }

    /**
     * Har bir toifa (talaba / xodim / ishga qabul qilinmagan) uchun alohida
     * avtomatik xulosa yozadi. Bo'sh qoldirilgan toifa o'tkazib yuboriladi.
     *
     * Bayroq (qizil/sariq/yashil) tanlangan bo'lsa, u ham shu oraliqdagi
     * natijalarga biriktiriladi.
     *
     * @param  array<string, string|null>  $conclusionsByRole
     * @param  array<string, string|null>  $flagsByRole
     * @return array<string, int>
     */
    public function updateAutomaticConclusions(
        int $moduleId,
        int $minScore,
        int $maxScore,
        array $conclusionsByRole,
        bool $overwriteExisting,
        array $flagsByRole = [],
    ): array {
        $updatedByRole = [];

        foreach (ReportAudience::roles() as $role) {
            $automaticConclusion = trim((string) ($conclusionsByRole[$role] ?? ''));
            $flag = $flagsByRole[$role] ?? null;
            $flag = RiskFlag::isValid($flag) ? $flag : null;

            // Na xulosa, na bayroq berilgan bo'lsa — bu toifa o'zgarmaydi.
            if ($automaticConclusion === '' && $flag === null) {
                continue;
            }

            $query = DB::table('users_tests_results')
                ->where('module_id', $moduleId)
                ->whereIn('user_id', function ($query) use ($moduleId, $minScore, $maxScore, $role): void {
                    $query
                        ->fromSub($this->filteredQuery($moduleId, $minScore, $maxScore, [$role]), 'filtered_users')
                        ->select('id');
                });

            // Ustidan yozish o'chiq bo'lsa, faqat xulosasi bo'shlari yangilanadi.
            // Bayroq yolg'iz yuborilganda bu cheklov qo'llanmaydi.
            if (! $overwriteExisting && $automaticConclusion !== '') {
                $query->where(function ($query): void {
                    $query
                        ->whereNull('result_real')
                        ->orWhere('result_real', '');
                });
            }

            $payload = ['updated_at' => now()];

            if ($automaticConclusion !== '') {
                $payload['result_real'] = $automaticConclusion;
            }

            if ($flag !== null) {
                $payload['flag'] = $flag;
            }

            $updatedByRole[$role] = (int) $query->update($payload);
        }

        return $updatedByRole;
    }

    public function flush(): void
    {
        Cache::forever($this->versionKey(), $this->cacheVersion() + 1);
    }

    /**
     * @param  list<string>|null  $roles  null bo'lsa — modulni yecha oladigan barcha toifalar.
     */
    private function filteredQuery(int $moduleId, int $minScore, int $maxScore, ?array $roles = null)
    {
        return DB::query()
            ->fromSub($this->scoreTotalsQuery($moduleId), 'module_scores')
            ->join('users', 'users.id', '=', 'module_scores.user_id')
            ->leftJoin('faculities', 'faculities.id', '=', 'users.faculity_id')
            ->leftJoin('groups', 'groups.id', '=', 'users.group_id')
            ->whereIn('users.role', ReportAudience::normalize($roles))
            ->whereBetween('module_scores.score', [$minScore, $maxScore])
            ->join('users_tests_results', function ($join) use ($moduleId) {
                $join->on('users_tests_results.user_id', '=', 'users.id')
                    ->where('users_tests_results.module_id', '=', $moduleId);
            })
            ->orderByDesc('module_scores.score')
            ->orderBy('users.name')
            ->selectRaw("
                users.id,
                users.login,
                users.name,
                users.role,
                COALESCE(faculities.name, '-') as faculity_name,
                COALESCE(groups.name, '-') as group_name,
                COALESCE(users.level, '-') as level,
                users_tests_results.flag as flag,
                module_scores.score
            ");
    }

    private function scoreTotalsQuery(int $moduleId)
    {
        return DB::table('solve_tests')
            ->join('test_options', 'test_options.id', '=', 'solve_tests.test_option_id')
            // O'chirilgan modul hisobotda ham ko'rinmaydi.
            ->join('modules', function ($join) {
                $join->on('modules.id', '=', 'solve_tests.module_id')
                    ->where('modules.is_active', '=', true);
            })
            ->select('solve_tests.user_id', 'solve_tests.module_id')
            ->selectRaw('SUM(test_options.option_value) as score')
            ->where('solve_tests.module_id', $moduleId)
            ->whereNotNull('solve_tests.test_option_id')
            ->groupBy('solve_tests.user_id', 'solve_tests.module_id');
    }

    private function makeCacheKey(int $moduleId, int $minScore, int $maxScore, string $suffix, array $extra = []): string
    {
        return 'dashboard:module-score-range-report:v'.$this->cacheVersion().':'.$suffix.':'.md5(json_encode([
            'module_id' => $moduleId,
            'min_score' => $minScore,
            'max_score' => $maxScore,
            'extra' => $extra,
        ]));
    }

    private function cacheVersion(): int
    {
        return (int) Cache::get($this->versionKey(), 1);
    }

    private function versionKey(): string
    {
        return 'dashboard:module-score-range-report:version';
    }
}
