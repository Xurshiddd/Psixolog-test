<?php

namespace App\Services;

use App\Models\CriticalAlert;
use App\Models\TestOption;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Zudlik bilan ish olib boriladigan holatlar. Talaba xavfli deb belgilangan
 * variantni tanlaganda ogohlantirish yoziladi, sidebarda qizil belgi chiqadi.
 *
 * Faqat talabalar uchun: xodim va ishga qabul qilinmagan nomzodlarda bu oqim
 * ishlamaydi.
 */
class CriticalAlertService
{
    private const PENDING_COUNT_KEY = 'critical-alerts:pending-students-count';

    /**
     * Yuborilgan javoblar orasidan xavfli variantlarni topib ogohlantirish
     * yozadi. Takroriy javob uchun yangi ogohlantirish yaratilmaydi.
     *
     * @param  list<int>  $testOptionIds
     */
    public function recordForSubmission(User $user, int $moduleId, array $testOptionIds): int
    {
        if ($user->role !== 'student' || $testOptionIds === []) {
            return 0;
        }

        $criticalOptions = TestOption::query()
            ->whereIn('id', array_unique($testOptionIds))
            ->where('is_critical', true)
            ->get(['id', 'test_id']);

        if ($criticalOptions->isEmpty()) {
            return 0;
        }

        $now = now();

        $created = CriticalAlert::insertOrIgnore(
            $criticalOptions->map(fn (TestOption $option): array => [
                'user_id' => $user->id,
                'module_id' => $moduleId,
                'test_id' => $option->test_id,
                'test_option_id' => $option->id,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );

        $this->flush();

        return $created;
    }

    /**
     * Sidebardagi qizil belgi raqami — hal qilinmagan ogohlantirishi bor
     * talabalar soni (ogohlantirishlar soni emas).
     */
    public function pendingStudentsCount(): int
    {
        return (int) Cache::remember(
            self::PENDING_COUNT_KEY,
            now()->addMinutes(5),
            fn (): int => $this->pendingBaseQuery()->distinct()->count('critical_alerts.user_id')
        );
    }

    /**
     * Talabalar kesimidagi ro'yxat: har bir qatorda bitta talaba va uning
     * hal qilinmagan xavfli javoblari.
     */
    public function paginateStudents(bool $onlyPending = true, int $perPage = 15): LengthAwarePaginator
    {
        $query = User::query()
            ->where('users.role', 'student')
            ->select(['users.id', 'users.name', 'users.login', 'users.phone', 'users.group_id', 'users.faculity_id'])
            ->with(['group:id,name', 'faculity:id,name'])
            ->whereHas('criticalAlerts', fn ($q) => $onlyPending ? $q->visibleModule()->pending() : $q->visibleModule());

        if ($onlyPending) {
            $query
                ->withCount(['criticalAlerts as pending_alerts_count' => fn ($q) => $q->visibleModule()->pending()])
                ->addSelect([
                    'last_alert_at' => CriticalAlert::query()
                        ->visibleModule()
                        ->pending()
                        ->selectRaw('MAX(created_at)')
                        ->whereColumn('critical_alerts.user_id', 'users.id'),
                ]);
        } else {
            $query
                ->withCount([
                    'criticalAlerts as pending_alerts_count' => fn ($q) => $q->visibleModule()->pending(),
                    'criticalAlerts as resolved_alerts_count' => fn ($q) => $q->visibleModule()->resolved(),
                ])
                ->addSelect([
                    'last_alert_at' => CriticalAlert::query()
                        ->visibleModule()
                        ->selectRaw('MAX(created_at)')
                        ->whereColumn('critical_alerts.user_id', 'users.id'),
                ]);
        }

        return $query->orderByDesc('last_alert_at')->paginate($perPage)->withQueryString();
    }

    /**
     * Bitta talabaning ogohlantirishlari — jadvalda ochib ko'rsatish uchun.
     *
     * @param  list<int>  $userIds
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, CriticalAlert>>
     */
    public function alertsForUsers(array $userIds, bool $onlyPending = true)
    {
        if ($userIds === []) {
            return collect();
        }

        return CriticalAlert::query()
            ->visibleModule()
            ->when($onlyPending, fn ($query) => $query->pending())
            ->whereIn('user_id', $userIds)
            ->with(['module:id,name', 'test:id,question', 'testOption:id,option_text', 'resolver:id,name'])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('user_id');
    }

    /**
     * Talabaning barcha kutilayotgan ogohlantirishlarini "hal qilindi" qiladi.
     */
    public function resolveForStudent(User $student, User $resolver): int
    {
        $resolved = CriticalAlert::query()
            ->pending()
            ->where('user_id', $student->id)
            ->update([
                'resolved_at' => now(),
                'resolved_by' => $resolver->id,
                'updated_at' => now(),
            ]);

        $this->flush();

        return $resolved;
    }

    public function flush(): void
    {
        Cache::forget(self::PENDING_COUNT_KEY);
    }

    private function pendingBaseQuery()
    {
        return DB::table('critical_alerts')
            ->join('users', 'users.id', '=', 'critical_alerts.user_id')
            ->join('modules', 'modules.id', '=', 'critical_alerts.module_id')
            ->whereNull('critical_alerts.resolved_at')
            ->where('users.role', 'student')
            // Statusi o'chirilgan modul platformada yo'qday ko'rinadi.
            ->where('modules.is_active', true);
    }
}
