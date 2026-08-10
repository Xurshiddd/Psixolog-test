<?php

namespace App\Http\Controllers;

use App\Application\AdminGuests\Data\AdminGuestFilters;
use App\Application\AdminGuests\Services\AdminGuestExportService;
use App\Application\AdminGuests\Services\BuildAdminGuestPages;
use App\Application\AdminStudents\Services\AdminStudentDiagnosisService;
use App\Application\AdminStudents\Services\AdminStudentRecordService;
use App\Application\AdminStudents\Services\BuildAdminStudentPages;
use App\Http\Requests\AdminGuestFilterRequest;
use App\Http\Requests\StoreUserPassportRequest;
use App\Http\Requests\SyncStudentCategoriesRequest;
use App\Http\Requests\UpdateStudentDiagnosisRequest;
use App\Models\Employee;
use App\Models\User;
use App\Services\HemisEmployeeDirectoryService;
use App\Services\UserPassportService;
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
        private HemisEmployeeDirectoryService $hemisEmployeeDirectory,
        private UserPassportService $userPassportService,
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

    /**
     * Modal qidiruvi: HEMIS ID bo'yicha avval o'z bazamizdan, topilmasa
     * HEMIS'dan qidiradi. JSON qaytaradi (Inertia emas).
     */
    public function employeeSearch(Request $request, User $user)
    {
        abort_unless($user->role === 'guest', 404);

        $data = $request->validate([
            'hemis_id' => ['required', 'string', 'max:50'],
        ]);

        $hemisId = trim($data['hemis_id']);

        // 1) Avval o'z bazamiz: shu HEMIS ID'li hodim bormi?
        $localEmployee = Employee::query()
            ->with('user:id,name,picture')
            ->where('employee_id_number', $hemisId)
            ->orWhere('external_id', $hemisId)
            ->first();

        if ($localEmployee && $localEmployee->user) {
            return response()->json([
                'source' => 'local',
                'local' => [
                    'user_id' => $localEmployee->user->id,
                    'name' => $localEmployee->user->name,
                    'picture' => $localEmployee->user->picture,
                    'profile_url' => route('admin.employees.show', $localEmployee->user->id),
                ],
                'hemis' => null,
            ]);
        }

        // 2) HEMIS'dan qidirish.
        $hemis = $this->hemisEmployeeDirectory->findByHemisId($hemisId);

        if ($hemis === null) {
            return response()->json([
                'source' => 'none',
                'local' => null,
                'hemis' => null,
                'message' => 'Bu HEMIS ID bo\'yicha ma\'lumot topilmadi.',
            ]);
        }

        return response()->json([
            'source' => 'hemis',
            'local' => null,
            'hemis' => $hemis,
        ]);
    }

    /**
     * Guest'ni allaqachon bazada mavjud xodim profiliga birlashtiradi:
     * guest yozuvi ro'yxatdan yo'qoladi, test natijalari esa o'sha xodim
     * profilida "ishga qabul qilinmagan vaqtdagi natijalar" sifatida ko'rinadi.
     */
    public function mergeIntoEmployee(Request $request, User $user)
    {
        abort_unless($user->role === 'guest', 404);

        $data = $request->validate([
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $target = User::query()->findOrFail($data['target_user_id']);
        abort_unless($target->role === 'employee', 422, 'Tanlangan foydalanuvchi hodim emas.');

        DB::transaction(function () use ($user, $target): void {
            $user->guest()->update(['application_status' => 'accepted']);
            $user->update(['merged_into_user_id' => $target->id]);
        });

        return redirect()
            ->route('admin.employees.show', $target->id)
            ->with('success', 'Guest mavjud hodim profiliga birlashtirildi. Test natijalari o\'sha profilda ko\'rinadi.');
    }

    public function updateStatus(Request $request, User $user)
    {
        abort_unless($user->role === 'guest', 404);

        $data = $request->validate([
            'status' => ['required', 'in:pending,accepted,rejected'],
            'hemis_id' => ['required_if:status,accepted', 'nullable', 'string', 'max:50'],
        ]);

        // Rad etish yoki kutilmoqda — oddiy holat yangilanishi.
        if ($data['status'] !== 'accepted') {
            $user->guest()->update(['application_status' => $data['status']]);

            return redirect()->back()->with('success', 'Ariza holati yangilandi.');
        }

        // Qabul qilish — HEMIS ID majburiy, HEMIS'dan ma'lumot olinadi.
        $hemis = $this->hemisEmployeeDirectory->findByHemisId((string) $data['hemis_id']);

        if ($hemis === null) {
            return redirect()->back()->with('error', 'Bu HEMIS ID bo\'yicha ma\'lumot topilmadi. Hodimga o\'tkazib bo\'lmadi.');
        }

        DB::transaction(function () use ($user, $hemis): void {
            $user->guest()->update(['application_status' => 'accepted']);

            $user->update(array_filter([
                'role' => 'employee',
                'name' => $hemis['name'] ?: $user->name,
                'login' => is_numeric($hemis['employee_id_number']) ? (int) $hemis['employee_id_number'] : $user->login,
                'picture' => $hemis['image'] ?: $user->picture,
                'birth_date' => $hemis['birth_date'] ?: $user->birth_date,
            ], fn ($value) => $value !== null));

            Employee::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'external_id' => $hemis['external_id'],
                    'employee_id_number' => $hemis['employee_id_number'],
                    'staff_position' => $hemis['staff_position'],
                    'employee_type_name' => $hemis['employee_type_name'],
                    'department_name' => $hemis['department_name'],
                    'image' => $hemis['image'],
                    'synced_at' => now(),
                ]
            );
        });

        return redirect()
            ->route('admin.employees.show', $user->id)
            ->with('success', 'Nomzod HEMIS ma\'lumotlari asosida ishga qabul qilindi va hodimlar ro\'yxatiga o\'tkazildi.');
    }

    public function exportPassportPdf(StoreUserPassportRequest $request, User $user)
    {
        abort_unless($user->role === 'guest', 404);

        return $this->userPassportService->downloadGeneratedPassport($user, $request->validated());
    }

    public function downloadSavedPassportPdf(User $user)
    {
        abort_unless($user->role === 'guest', 404);

        return $this->userPassportService->downloadSavedPassport($user);
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
