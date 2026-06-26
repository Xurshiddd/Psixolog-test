<?php

namespace App\Http\Controllers;

use App\Application\AdminEmployees\Data\AdminEmployeeFilters;
use App\Application\AdminEmployees\Services\AdminEmployeeExportService;
use App\Application\AdminEmployees\Services\BuildAdminEmployeePages;
use App\Application\AdminStudents\Services\AdminStudentDiagnosisService;
use App\Application\AdminStudents\Services\AdminStudentRecordService;
use App\Application\AdminStudents\Services\BuildAdminStudentPages;
use App\Http\Requests\AdminEmployeeFilterRequest;
use App\Http\Requests\SyncStudentCategoriesRequest;
use App\Http\Requests\UpdateStudentDiagnosisRequest;
use App\Models\User;
use Illuminate\Http\Response;
use Inertia\Inertia;

class AdminEmployeeController extends Controller
{
    public function __construct(
        private BuildAdminEmployeePages $buildAdminEmployeePages,
        private AdminEmployeeExportService $adminEmployeeExportService,
        private AdminStudentRecordService $adminStudentRecordService,
        private BuildAdminStudentPages $buildAdminStudentPages,
        private AdminStudentDiagnosisService $adminStudentDiagnosisService,
    ) {}

    public function index(AdminEmployeeFilterRequest $request)
    {
        return Inertia::render(
            'Admin/Employee/Index',
            $this->buildAdminEmployeePages->indexProps(
                AdminEmployeeFilters::fromArray($request->validated())
            )
        );
    }

    public function show(AdminEmployeeFilterRequest $request, User $user)
    {
        abort_unless($user->role === 'employee', 404);

        return Inertia::render(
            'Admin/Employee/Show',
            $this->buildAdminEmployeePages->showProps(
                $user,
                AdminEmployeeFilters::fromArray($request->validated())
            )
        );
    }

    public function exportExcel(AdminEmployeeFilterRequest $request)
    {
        return $this->adminEmployeeExportService->downloadExcel(
            AdminEmployeeFilters::fromArray($request->validated())
        );
    }

    public function syncCategories(SyncStudentCategoriesRequest $request, User $user)
    {
        abort_unless($user->role === 'employee', 404);

        $this->adminStudentRecordService->syncCategories(
            $user,
            $request->validated('category_ids', [])
        );

        return redirect()->back()->with('success', 'Kategoriyalar muvaffaqiyatli bog\'landi');
    }

    public function showResult(User $user, int $moduleId)
    {
        abort_unless($user->role === 'employee', 404);

        $props = $this->buildAdminStudentPages->showResultProps($user, $moduleId);

        abort_if($props === null, Response::HTTP_NOT_FOUND, 'Natija topilmadi.');

        $props['basePath'] = '/admin/employees';
        $props['backTitle'] = 'Xodimlar';

        return Inertia::render('Admin/Student/Result', $props);
    }

    public function updateDiagnosis(UpdateStudentDiagnosisRequest $request, User $user, int $moduleId)
    {
        abort_unless($user->role === 'employee', 404);

        $updated = $this->adminStudentDiagnosisService->update($user, $moduleId, $request->validated('diagnosis'));

        abort_if($updated === 0, Response::HTTP_NOT_FOUND, 'Natija topilmadi.');

        return redirect()->back()->with('success', 'Diagnostika muvaffaqiyatli saqlandi');
    }

    public function generateAiDiagnosis(User $user, int $moduleId)
    {
        abort_unless($user->role === 'employee', 404);

        $result = $this->adminStudentDiagnosisService->generate($user, $moduleId);

        return response()->json($result['payload'], $result['status']);
    }

    public function streamAiDiagnosis(User $user, int $moduleId)
    {
        abort_unless($user->role === 'employee', 404);

        $result = $this->adminStudentDiagnosisService->stream($user, $moduleId);

        if (isset($result['payload'])) {
            return response()->json($result['payload'], $result['status']);
        }

        return response()->stream($result['callback'], $result['status'], $result['headers']);
    }
}
