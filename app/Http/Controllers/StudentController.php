<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolveTestRequest;
use App\Models\Module;
use App\Services\BlockSequenceService;
use App\Services\StudentTestServices;

class StudentController extends Controller
{
    public function __construct(
        private StudentTestServices $studentTestServices,
        private BlockSequenceService $blockSequenceService,
    ) {}

    public function index()
    {
        $user = auth()->user();

        $modules = Module::query()
            ->where('is_active', true)
            ->forAudience($user->role)
            ->withCount('tests')
            ->get();

        return inertia('Student/Tests', [
            'blocks' => $this->blockSequenceService->groupedForUser($user, $modules),
        ]);
    }

    public function takeTest($moduleId)
    {
        $user = auth()->user();

        $module = Module::with(['tests.options'])->findOrFail($moduleId);

        abort_unless($module->is_active, 404, 'Bu modul hozircha mavjud emas.');
        abort_unless($module->isForAudience($user->role), 403, 'Bu test siz uchun mo‘ljallanmagan.');

        // Blok ketma-ketligi: oldingi modul yechilmagan bo'lsa, testga kirilmaydi.
        if ($blocking = $this->blockSequenceService->blockingModuleName($user, $module)) {
            return redirect()
                ->route('student_test_index')
                ->with('error', "Avval \"{$blocking}\" modulini yechishingiz kerak.");
        }

        // Check if user has already solved this module
        $existingResult = $module->usersTestsResults()
            ->where('user_id', $user->id)
            ->first();

        return inertia('Student/TakeTest', [
            'module' => $module,
            'existingResult' => $existingResult ? [
                'result_fake' => $existingResult->pivot->result_fake,
                'result_real' => $existingResult->pivot->result_real,
            ] : null,
        ]);
    }

    public function submitTest(SolveTestRequest $request)
    {
        $user = auth()->user();
        $module = Module::findOrFail($request->module_id);

        abort_unless($module->is_active, 404, 'Bu modul hozircha mavjud emas.');
        abort_unless($module->isForAudience($user->role), 403, 'Bu test siz uchun mo‘ljallanmagan.');

        if ($blocking = $this->blockSequenceService->blockingModuleName($user, $module)) {
            return redirect()->back()->with('error', "Avval \"{$blocking}\" modulini yechishingiz kerak.");
        }

        if ($user->usersTestsResults()->where('module_id', $request->module_id)->exists()) {
            return redirect()->back()->with('error', 'You have already submitted this test.');
        }

        $results = $this->studentTestServices->processBatchSubmission(
            $user->id,
            $request->module_id,
            $request->answers
        );

        return redirect()->back()->with($results);
    }
}
