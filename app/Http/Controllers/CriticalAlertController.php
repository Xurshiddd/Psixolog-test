<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CriticalAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * "Zudlik bilan" bo'limi: xavfli variantni tanlagan talabalar ro'yxati va
 * ularni "Hal qilindi" deb belgilash.
 */
class CriticalAlertController extends Controller
{
    public function __construct(private CriticalAlertService $criticalAlertService) {}

    public function index(Request $request)
    {
        $this->ensureAccess();

        $onlyPending = $request->query('status', 'pending') !== 'all';

        $students = $this->criticalAlertService->paginateStudents($onlyPending);
        $alerts = $this->criticalAlertService->alertsForUsers(
            collect($students->items())->pluck('id')->all(),
            $onlyPending,
        );

        return Inertia::render('Admin/CriticalAlerts/Index', [
            'students' => $students->through(fn (User $student): array => [
                'id' => $student->id,
                'name' => $student->name,
                'login' => $student->login,
                'phone' => $student->phone,
                'group_name' => $student->group?->name ?? '-',
                'faculity_name' => $student->faculity?->name ?? '-',
                'pending_alerts_count' => (int) ($student->pending_alerts_count ?? 0),
                'resolved_alerts_count' => (int) ($student->resolved_alerts_count ?? 0),
                'last_alert_at' => $student->last_alert_at,
                'alerts' => collect($alerts->get($student->id, collect()))
                    ->map(fn ($alert): array => [
                        'id' => $alert->id,
                        'module_name' => $alert->module?->name ?? '-',
                        'question' => $alert->test?->question ?? '-',
                        'answer' => $alert->testOption?->option_text ?? '-',
                        'created_at' => $alert->created_at?->toDateTimeString(),
                        'resolved_at' => $alert->resolved_at?->toDateTimeString(),
                        'resolved_by' => $alert->resolver?->name,
                    ])
                    ->values(),
            ]),
            'filters' => ['status' => $onlyPending ? 'pending' : 'all'],
            'pendingStudentsCount' => $this->criticalAlertService->pendingStudentsCount(),
        ]);
    }

    public function resolve(User $student)
    {
        $this->ensureAccess();

        abort_unless($student->role === 'student', 404);

        $resolved = $this->criticalAlertService->resolveForStudent($student, Auth::user());

        return redirect()->back()->with(
            'success',
            $resolved > 0
                ? "{$student->name} bo'yicha {$resolved} ta holat hal qilindi deb belgilandi."
                : 'Hal qilinmagan holat topilmadi.'
        );
    }

    private function ensureAccess(): void
    {
        abort_unless(
            Auth::check() && in_array(Auth::user()?->role, ['admin', 'psiholog'], true),
            403
        );
    }
}
