<?php

namespace App\Http\Controllers;
use App\Http\Requests\SolveTestRequest;
use App\Models\Module;
use App\Services\StudentTestServices;

class StudentController extends Controller
{
    public function __construct(private StudentTestServices $studentTestServices)
    {
    }
    public function index()
    {
        return inertia(
            'Student/Tests',
        [
            'modules' => Module::with('tests.options')
                ->where('is_active', true)
                ->forAudience(auth()->user()->role)
                ->get(),
        ]
        );
    }

    public function takeTest($moduleId)
    {
        $module = Module::with([
            'tests.options'
        ])->findOrFail($moduleId);

        abort_unless($module->isForAudience(auth()->user()->role), 403, 'Bu test siz uchun mo‘ljallanmagan.');

        // Check if user has already solved this module
        $existingResult = $module->usersTestsResults()
            ->where('user_id', auth()->id())
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

        abort_unless($module->isForAudience($user->role), 403, 'Bu test siz uchun mo‘ljallanmagan.');

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
