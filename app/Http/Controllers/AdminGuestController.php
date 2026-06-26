<?php

namespace App\Http\Controllers;

use App\Application\AdminGuests\Data\AdminGuestFilters;
use App\Application\AdminGuests\Services\AdminGuestExportService;
use App\Application\AdminGuests\Services\BuildAdminGuestPages;
use App\Application\AdminStudents\Services\AdminStudentDiagnosisService;
use App\Application\AdminStudents\Services\AdminStudentRecordService;
use App\Application\AdminStudents\Services\BuildAdminStudentPages;
use App\Http\Requests\AdminGuestFilterRequest;
use App\Http\Requests\SyncStudentCategoriesRequest;
use App\Http\Requests\UpdateStudentDiagnosisRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminGuestController extends Controller
{
    public function __construct(
        private BuildAdminGuestPages $buildAdminGuestPages,
        private AdminGuestExportService $adminGuestExportService,
        private AdminStudentRecordService $adminStudentRecordService,
        private BuildAdminStudentPages $buildAdminStudentPages,
        private AdminStudentDiagnosisService $adminStudentDiagnosisService,
    ) {}

    public function index(AdminGuestFilterRequest $request)
    {
        return Inertia::render(
            'Admin/Guest/Index',
            $this->buildAdminGuestPages->indexProps(
                AdminGuestFilters::fromArray($request->validated())
            )
        );
    }

    public function show(AdminGuestFilterRequest $request, User $user)
    {
        abort_unless($user->role === 'guest', 404);

        return Inertia::render(
            'Admin/Guest/Show',
            $this->buildAdminGuestPages->showProps(
                $user,
                AdminGuestFilters::fromArray($request->validated())
            )
        );
    }

    public function exportExcel(AdminGuestFilterRequest $request)
    {
        return $this->adminGuestExportService->downloadExcel(
            AdminGuestFilters::fromArray($request->validated())
        );
    }

    public function syncCategories(SyncStudentCategoriesRequest $request, User $user)
    {
        abort_unless($user->role === 'guest', 404);

        $this->adminStudentRecordService->syncCategories(
            $user,
            $request->validated('category_ids', [])
        );

        return redirect()->back()->with('success', 'Kategoriyalar muvaffaqiyatli bog\'landi');
    }

    public function updateStatus(Request $request, User $user)
    {
        abort_unless($user->role === 'guest', 404);

        $data = $request->validate([
            'status' => ['required', 'in:pending,accepted,rejected'],
        ]);

        DB::transaction(function () use ($user, $data): void {
            $user->guest()->update(['application_status' => $data['status']]);

            if ($data['status'] === 'accepted') {
                $user->update(['role' => 'employee']);

                Employee::firstOrCreate(
                    ['user_id' => $user->id],
                    ['synced_at' => now()]
                );
            }
        });

        if ($data['status'] === 'accepted') {
            return redirect()
                ->route('admin.employees.show', $user->id)
                ->with('success', 'Nomzod ishga qabul qilindi va hodimlar ro\'yxatiga o\'tkazildi.');
        }

        return redirect()->back()->with('success', 'Ariza holati yangilandi.');
    }

    public function showResult(User $user, int $moduleId)
    {
        abort_unless($user->role === 'guest', 404);

        $props = $this->buildAdminStudentPages->showResultProps($user, $moduleId);

        abort_if($props === null, Response::HTTP_NOT_FOUND, 'Natija topilmadi.');

        $props['basePath'] = '/admin/guests';
        $props['backTitle'] = 'Ishga qabul qilinmaganlar';

        return Inertia::render('Admin/Student/Result', $props);
    }

    public function updateDiagnosis(UpdateStudentDiagnosisRequest $request, User $user, int $moduleId)
    {
        abort_unless($user->role === 'guest', 404);

        $updated = $this->adminStudentDiagnosisService->update($user, $moduleId, $request->validated('diagnosis'));

        abort_if($updated === 0, Response::HTTP_NOT_FOUND, 'Natija topilmadi.');

        return redirect()->back()->with('success', 'Diagnostika muvaffaqiyatli saqlandi');
    }

    public function generateAiDiagnosis(User $user, int $moduleId)
    {
        abort_unless($user->role === 'guest', 404);

        $result = $this->adminStudentDiagnosisService->generate($user, $moduleId);

        return response()->json($result['payload'], $result['status']);
    }

    public function streamAiDiagnosis(User $user, int $moduleId)
    {
        abort_unless($user->role === 'guest', 404);

        $result = $this->adminStudentDiagnosisService->stream($user, $moduleId);

        if (isset($result['payload'])) {
            return response()->json($result['payload'], $result['status']);
        }

        return response()->stream($result['callback'], $result['status'], $result['headers']);
    }
}
