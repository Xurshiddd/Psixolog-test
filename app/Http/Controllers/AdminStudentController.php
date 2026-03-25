<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Group;
use App\Models\Speciality;
use App\Models\Module;
use App\Models\SolveTest;
use App\Exports\StudentsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\StudentPdfExportService;
use App\Exports\StudentsExportWithDiagnosis;
use Illuminate\Support\Facades\DB;
use App\Ai\Agents\DiagnosisAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Response;

class AdminStudentController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'student')
            ->with(['group', 'speciality', 'faculity', 'usersTestsResults']);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('login', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->has('group_id') && $request->group_id) {
            $query->where('group_id', $request->group_id);
        }

        if ($request->has('speciality_id') && $request->speciality_id) {
            $query->where('speciality_id', $request->speciality_id);
        }

        if ($request->has('faculity_id') && $request->faculity_id) {
            $query->where('faculity_id', $request->faculity_id);
        }

        if ($request->has('level') && $request->level) {
            $query->where('level', $request->level);
        }

        
        if ($request->has('test_status') && $request->test_status) {
            if ($request->test_status === 'submitted') {
                $query->whereHas('usersTestsResults');
            } elseif ($request->test_status === 'not_submitted') {
                $query->whereDoesntHave('usersTestsResults');
            }
        }

        if ($request->has('category_id') && $request->category_id) {
            $query->whereHas('usersCategory', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        $students = $query->latest()->paginate(10);

        $groups = Group::orderBy('name')->get();
        $specialities = Speciality::orderBy('name')->get();
        $faculities = \App\Models\Faculity::orderBy('name')->get();
        $categories = \App\Models\Category::orderBy('name')->get();

        return Inertia::render('Admin/Student/Index', [
            'students' => $students,
            'groups' => $groups,
            'specialities' => $specialities,
            'faculities' => $faculities,
            'categories' => $categories,
            'filters' => [
                'search' => $request->get('search'),
                'faculity_id' => $request->get('faculity_id'),
                'speciality_id' => $request->get('speciality_id'),
                'group_id' => $request->get('group_id'),
                'level' => $request->get('level'),
                'test_status' => $request->get('test_status'),
                'category_id' => $request->get('category_id'),
            ]
        ]);
    }

    public function show(Request $request, User $user)
    {
        $user->load(['group', 'speciality', 'usersTestsResults']);

        return Inertia::render('Admin/Student/Show', [
            'student' => $user->load('usersCategory'),
            'results' => $user->usersTestsResults,
            'allCategories' => \App\Models\Category::all(),
            'filters' => [
                'group_id' => $request->get('group_id'),
                'speciality_id' => $request->get('speciality_id'),
                'test_status' => $request->get('test_status'),
            ],
            'page' => $request->get('page', 1)
        ]);
    }

    public function showResult(User $user, $moduleId)
    {
        $this->ensureDiagnosisAccess();

        $module = Module::with(['tests.options'])->findOrFail($moduleId);

        $answers = SolveTest::where('user_id', $user->id)
            ->where('module_id', $moduleId)
            ->get()
            ->groupBy('test_id');

        $result = $user->usersTestsResults()
            ->where('module_id', $moduleId)
            ->first();

        abort_if(! $result, Response::HTTP_NOT_FOUND, 'Natija topilmadi.');

        return Inertia::render('Admin/Student/Result', [
            'student' => $user,
            'module' => $module,
            'answers' => $answers,
            'diagnosis' => $result->pivot->diagnosis,
            'generatedDiagnosis' => $result->pivot->result_real,
        ]);
    }

    public function updateDiagnosis(Request $request, User $user, $moduleId): RedirectResponse
    {
        $this->ensureDiagnosisAccess();

        $request->validate([
            'diagnosis' => 'nullable|string|max:10000',
        ]);

        $updated = $user->usersTestsResults()->updateExistingPivot($moduleId, [
            'diagnosis' => $request->diagnosis,
        ]);

        abort_if($updated === 0, Response::HTTP_NOT_FOUND, 'Natija topilmadi.');

        return redirect()->back()->with('success', 'Diagnostika muvaffaqiyatli saqlandi');
    }

    public function generateAiDiagnosis(User $user, $moduleId): JsonResponse
    {
        $this->ensureDiagnosisAccess();

        [$provider, $providerError] = $this->resolveDiagnosisProvider();

        if ($providerError !== null) {
            return response()->json([
                'error' => $providerError,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        [$module, $answers, $result] = $this->loadDiagnosisGenerationContext($user, $moduleId);

        $prompt = $this->buildDiagnosisPrompt($user, $module, $answers, $result);

        try {
            $agent = app(DiagnosisAgent::class);
            $response = $agent->prompt($prompt, provider: $provider);
            $text = trim($response->text);

            if ($text === '') {
                return response()->json([
                    'error' => 'AI provider bo\'sh javob qaytardi. Qaytadan urinib ko\'ring.',
                ], Response::HTTP_BAD_GATEWAY);
            }

            return response()->json([
                'diagnosis' => $text,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $this->formatDiagnosisExceptionMessage($provider, $e),
            ], $this->diagnosisExceptionStatus($e));
        }
    }

    public function streamAiDiagnosis(User $user, $moduleId)
    {
        $this->ensureDiagnosisAccess();

        [$provider, $providerError] = $this->resolveDiagnosisProvider();

        if ($providerError !== null) {
            return response()->json([
                'error' => $providerError,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        [$module, $answers, $result] = $this->loadDiagnosisGenerationContext($user, $moduleId);

        $prompt = $this->buildDiagnosisPrompt($user, $module, $answers, $result);

        return response()->stream(function () use ($prompt, $provider) {
            try {
                $agent = app(DiagnosisAgent::class);
                $stream = $agent->stream($prompt, provider: $provider);

                foreach ($stream as $event) {
                    echo 'data: '.((string) $event)."\n\n";
                    @ob_flush();
                    flush();
                }
            } catch (\Throwable $e) {
                echo 'data: '.json_encode([
                    'type' => 'error',
                    'message' => $this->formatDiagnosisExceptionMessage($provider, $e),
                ], JSON_UNESCAPED_UNICODE)."\n\n";
            }

            echo "data: [DONE]\n\n";
            @ob_flush();
            flush();
        }, Response::HTTP_OK, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function getFilteredStudents(Request $request)
    {
        $query = User::where('role', 'student')
            ->with(['group', 'speciality', 'faculity', 'usersTestsResults']);

        // Filter by group
        if ($request->has('group_id') && $request->group_id) {
            $query->where('group_id', $request->group_id);
        }

        // Filter by speciality
        if ($request->has('speciality_id') && $request->speciality_id) {
            $query->where('speciality_id', $request->speciality_id);
        }

        // Filter by faculity
        if ($request->has('faculity_id') && $request->faculity_id) {
            $query->where('faculity_id', $request->faculity_id);
        }

        // Filter by level
        if ($request->has('level') && $request->level) {
            $query->where('level', $request->level);
        }

        // Filter by test status
        if ($request->has('test_status') && $request->test_status) {
            if ($request->test_status === 'submitted') {
                $query->whereHas('usersTestsResults');
            } elseif ($request->test_status === 'not_submitted') {
                $query->whereDoesntHave('usersTestsResults');
            }
        }

        if ($request->has('category_id') && $request->category_id) {
            $query->whereHas('usersCategory', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        return $query->latest()->get();
    }

    public function syncCategories(Request $request, User $user)
    {
        $request->validate([
            'category_ids' => 'array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $user->usersCategory()->sync($request->category_ids);

        return redirect()->back()->with('success', 'Kategoriyalar muvaffaqiyatli bog\'landi');
    }

    public function exportExcel(Request $request)
    {
        $students = $this->getFilteredStudents($request);
        $modules = Module::orderBy('name')->get();
        $timestamp = now()->format('Y-m-d_H-i-s');
        return Excel::download(new StudentsExport($students, $modules), "talabalar_$timestamp.xlsx");
    }

    private function ensureDiagnosisAccess(): void
    {
        abort_unless(
            auth()->check() && in_array(auth()->user()->role, ['admin', 'psiholog'], true),
            Response::HTTP_FORBIDDEN
        );
    }

    private function resolveDiagnosisProvider(): array
    {
        $provider = (string) config('ai.diagnosis_provider', 'deepseek');
        $providerKey = config("ai.providers.{$provider}.key");

        if (blank($providerKey)) {
            return [$provider, strtoupper($provider) . ' API kaliti sozlanmagan. .env faylida kerakli provider kalitini kiriting.'];
        }

        return [$provider, null];
    }

    private function loadDiagnosisGenerationContext(User $user, $moduleId): array
    {
        $module = Module::with(['tests.options'])->findOrFail($moduleId);

        $answers = SolveTest::where('user_id', $user->id)
            ->where('module_id', $moduleId)
            ->get()
            ->groupBy('test_id');

        $result = $user->usersTestsResults()
            ->where('module_id', $moduleId)
            ->first();

        abort_if(! $result, Response::HTTP_NOT_FOUND, 'Natija topilmadi.');

        return [$module, $answers, $result];
    }

    private function diagnosisExceptionStatus(\Throwable $e): int
    {
        $message = mb_strtolower($e->getMessage());

        if (str_contains($message, 'insufficient credits') || str_contains($message, 'quota')) {
            return Response::HTTP_PAYMENT_REQUIRED;
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    private function formatDiagnosisExceptionMessage(string $provider, \Throwable $e): string
    {
        $message = mb_strtolower($e->getMessage());

        if (str_contains($message, 'insufficient credits') || str_contains($message, 'quota')) {
            return strtoupper($provider) . ' hisobida kredit yoki quota yetarli emas. Provider kabinetida balansni to\'ldiring yoki .env da AI_DIAGNOSIS_PROVIDER ni boshqa providerga almashtiring.';
        }

        if (str_contains($message, 'rate limit')) {
            return strtoupper($provider) . ' vaqtinchalik so\'rov limitiga yetdi. Birozdan keyin qayta urinib ko\'ring.';
        }

        return 'AI xulosa olishda xatolik: ' . Str::limit($e->getMessage(), 250);
    }

    private function buildDiagnosisPrompt(User $user, Module $module, $answers, Module $result): string
    {
        $lines = [
            'AI uchun diagnostika konteksti:',
            "Talaba: {$user->name}",
            "Modul nomi: {$module->name}",
            'Modul tavsifi: ' . ($module->description ?: "Mavjud emas"),
            'Tizimdagi avtomatik xulosa: ' . ($result->pivot->result_real ?: "Mavjud emas"),
            'Psixologning oldingi xulosasi: ' . ($result->pivot->diagnosis ?: "Mavjud emas"),
            '',
            "Muhim eslatma:",
            "- Har bir savolni modul tavsifi bilan birga tahlil qiling.",
            "- Tanlangan va tanlanmagan javoblarning ikkalasini ham hisobga oling.",
            "- Faqat berilgan ma'lumotga tayangan holda xulosa yozing.",
            '',
            "Test savollari va javoblar tafsiloti:",
            '',
        ];

        $valueCounts = [];

        foreach ($module->tests as $index => $test) {
            $num = $index + 1;
            $lines[] = "Savol {$num}: {$test->question}";
            $lines[] = "Savol turi: {$test->type}";

            $testAnswers = $answers->get($test->id, collect());

            if ($test->type === 'text') {
                $answer = trim((string) ($testAnswers->first()?->answer ?? 'Javob berilmagan'));
                $lines[] = "Talabaning yozma javobi: {$answer}";
                $lines[] = '';

                continue;
            }

            $selectedOptions = [];
            $unselectedOptions = [];

            foreach ($test->options as $option) {
                $selected = $testAnswers->contains('test_option_id', $option->id);
                $value = $option->option_value ?? 0;
                $optionLine = "{$option->option_text} (ball: {$value})";

                if ($selected) {
                    $valueCounts[$value] = ($valueCounts[$value] ?? 0) + 1;
                    $selectedOptions[] = $optionLine;
                } else {
                    $unselectedOptions[] = $optionLine;
                }
            }

            $lines[] = 'Tanlangan javoblar:';
            if ($selectedOptions === []) {
                $lines[] = ' - Tanlangan javob yo\'q';
            } else {
                foreach ($selectedOptions as $selectedOption) {
                    $lines[] = " - {$selectedOption}";
                }
            }

            $lines[] = 'Tanlanmagan javoblar:';
            if ($unselectedOptions === []) {
                $lines[] = ' - Tanlanmagan javob yo\'q';
            } else {
                foreach ($unselectedOptions as $unselectedOption) {
                    $lines[] = " - {$unselectedOption}";
                }
            }

            $lines[] = '';
        }

        if ($valueCounts !== []) {
            arsort($valueCounts);

            $lines[] = 'Tanlangan variantlar bo‘yicha umumiy taqsimot:';
            foreach ($valueCounts as $value => $count) {
                $lines[] = " - Ball {$value}: {$count} ta tanlov";
            }
            $lines[] = '';
        }

        $lines[] = "Vazifa:";
        $lines[] = "Talabaning psixologik holati haqida professional, ehtiyotkor va amaliy xulosa yozing.";
        $lines[] = "Xulosani yozishda modul nomi, modul tavsifi, har bir savol va tanlangan/tanlanmagan barcha javoblardan foydalaning.";
        $lines[] = "Natijani faqat quyidagi 2 bo'limda yozing:";
        $lines[] = "1. E'tibor talab qiladigan jihatlar.";
        $lines[] = "2. Tavsiyalar.";
        $lines[] = "Har bir bo'lim 1-3 jumladan oshmasin.";
        $lines[] = "Medikal tashxis qo'ymang va mavjud ma'lumotdan tashqariga chiqib keskin hukm qilmang.";

        return implode("\n", $lines);
    }
    public function exportExcelWithDiagnosis(Request $request)
    {
        $students = $this->getFilteredStudents($request);
        $modules = Module::orderBy('name')->get();
        $timestamp = now()->format('Y-m-d_H-i-s');
        return Excel::download(new StudentsExportWithDiagnosis($students, $modules), "talabalar_$timestamp.xlsx");
    }

    public function exportPdf(Request $request, StudentPdfExportService $pdfExportService)
    {
        $students = $this->getFilteredStudents($request);
        $timestamp = now()->format('Y-m-d_H-i-s');
        $pdf = $pdfExportService->generatePdf($students);
        return $pdf->download("talabalar_$timestamp.pdf");
    }
    public function destroyResult(User $student, $resultId)
    {
        DB::transaction(function () use ($student, $resultId) {
            DB::table('solve_tests')->where('user_id', $student->id)->where('module_id', $resultId)->delete();
            DB::table('users_tests_results')->where('user_id', $student->id)->where('module_id', $resultId)->delete();
        });
        return to_route('admin.students.show', $student->id)->with('success', 'Natija muvaffaqiyatli o\'chirildi');
    }
}
