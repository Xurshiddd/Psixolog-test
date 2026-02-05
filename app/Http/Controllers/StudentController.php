<?php

namespace App\Http\Controllers;
use App\Http\Requests\SolveTestRequest;
use App\Models\Module;
use App\Services\StudentTestServices;

class StudentController extends Controller
{
    public function __construct(private StudentTestServices $studentTestServices) {}
    public function index()
    {
        return inertia(
            'Student/Tests',
            [
                'modules' => Module::with('tests.options')->where('is_active', true)->get(),
            ]
        );
    }

    public function takeTest($moduleId)
    {
        $module = Module::with([
            'tests.options'
        ])->findOrFail($moduleId);

        return inertia('Student/TakeTest', [
            'module' => $module,
        ]);
    }

    public function submitTest(SolveTestRequest $request)
    {
        $results = $this->studentTestServices->processBatchSubmission(
            auth()->id(),
            $request->module_id,
            $request->answers
        );
        return redirect()->back()->with($results);
    }
}
