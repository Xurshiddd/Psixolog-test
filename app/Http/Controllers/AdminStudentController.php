<?php

namespace App\Http\Controllers;

use App\Application\AdminStudents\Data\AdminStudentFilters;
use App\Application\AdminStudents\Services\AdminStudentDiagnosisService;
use App\Application\AdminStudents\Services\AdminStudentExportService;
use App\Application\AdminStudents\Services\AdminStudentPassportService;
use App\Application\AdminStudents\Services\AdminStudentRecordService;
use App\Application\AdminStudents\Services\BuildAdminStudentPages;
use App\Http\Requests\AdminStudentFilterRequest;
use App\Http\Requests\StoreStudentPassportRequest;
use App\Http\Requests\SyncStudentCategoriesRequest;
use App\Http\Requests\UpdateStudentDiagnosisRequest;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminStudentController extends Controller
{
    public function __construct(
        private BuildAdminStudentPages $buildAdminStudentPages,
        private AdminStudentDiagnosisService $adminStudentDiagnosisService,
        private AdminStudentExportService $adminStudentExportService,
        private AdminStudentPassportService $adminStudentPassportService,
        private AdminStudentRecordService $adminStudentRecordService,
    ) {}

    public function index(AdminStudentFilterRequest $request)
    {
        return Inertia::render(
            'Admin/Student/Index',
            $this->buildAdminStudentPages->indexProps(
                AdminStudentFilters::fromArray($request->validated())
            )
        );
    }

    public function show(AdminStudentFilterRequest $request, User $user)
    {
        return Inertia::render(
            'Admin/Student/Show',
            $this->buildAdminStudentPages->showProps(
                $user,
                AdminStudentFilters::fromArray($request->validated())
            )
        );
    }

    public function showResult(User $user, int $moduleId)
    {
        $this->ensureDiagnosisAccess();

        $props = $this->buildAdminStudentPages->showResultProps($user, $moduleId);

        abort_if($props === null, Response::HTTP_NOT_FOUND, 'Natija topilmadi.');

        return Inertia::render('Admin/Student/Result', $props);
    }

    public function updateDiagnosis(UpdateStudentDiagnosisRequest $request, User $user, int $moduleId)
    {
        $updated = $this->adminStudentDiagnosisService->update(
            $user,
            $moduleId,
            $request->validated('diagnosis')
        );

        abort_if($updated === 0, Response::HTTP_NOT_FOUND, 'Natija topilmadi.');

        return redirect()->back()->with('success', 'Diagnostika muvaffaqiyatli saqlandi');
    }

    public function generateAiDiagnosis(User $user, int $moduleId)
    {
        $this->ensureDiagnosisAccess();

        $result = $this->adminStudentDiagnosisService->generate($user, $moduleId);

        return response()->json($result['payload'], $result['status']);
    }

    public function streamAiDiagnosis(User $user, int $moduleId)
    {
        $this->ensureDiagnosisAccess();

        $result = $this->adminStudentDiagnosisService->stream($user, $moduleId);

        if (isset($result['payload'])) {
            return response()->json($result['payload'], $result['status']);
        }

        return response()->stream($result['callback'], $result['status'], $result['headers']);
    }

    public function syncCategories(SyncStudentCategoriesRequest $request, User $user)
    {
        $this->adminStudentRecordService->syncCategories(
            $user,
            $request->validated('category_ids', [])
        );

        return redirect()->back()->with('success', 'Kategoriyalar muvaffaqiyatli bog\'landi');
    }

    public function exportExcel(AdminStudentFilterRequest $request)
    {
        return $this->adminStudentExportService->downloadExcel(
            AdminStudentFilters::fromArray($request->validated())
        );
    }

    public function exportExcelWithDiagnosis(AdminStudentFilterRequest $request)
    {
        return $this->adminStudentExportService->downloadDiagnosisExcel(
            AdminStudentFilters::fromArray($request->validated())
        );
    }

    public function exportPdf(AdminStudentFilterRequest $request)
    {
        return $this->adminStudentExportService->downloadPdf(
            AdminStudentFilters::fromArray($request->validated())
        );
    }

    public function exportStudentPassportPdf(StoreStudentPassportRequest $request, User $user)
    {
        return $this->adminStudentPassportService->downloadGeneratedPassport(
            $user,
            $request->validated()
        );
    }

    public function downloadSavedStudentPassportPdf(User $user)
    {
        return $this->adminStudentPassportService->downloadSavedPassport($user);
    }

    public function destroyResult(User $student, int $resultId)
    {
        $this->ensureStudentManagementAccess();

        $this->adminStudentRecordService->destroyResult($student, $resultId);

        return to_route('admin.students.show', $student->id)->with('success', 'Natija muvaffaqiyatli o\'chirildi');
    }

    private function ensureDiagnosisAccess(): void
    {
        abort_unless(
            Auth::check() && in_array(Auth::user()?->role, ['admin', 'psiholog'], true),
            Response::HTTP_FORBIDDEN
        );
    }

    private function ensureStudentManagementAccess(): void
    {
        abort_unless(
            Auth::check() && in_array(Auth::user()?->role, ['admin', 'psiholog'], true),
            Response::HTTP_FORBIDDEN
        );
    }
}
