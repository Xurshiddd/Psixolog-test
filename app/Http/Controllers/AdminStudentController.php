<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminStudentController extends Controller
{
    public function index()
    {
        $students = User::where('role', 'student')
            ->with(['group', 'speciality', 'usersTestsResults'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Admin/Student/Index', [
            'students' => $students
        ]);
    }

    public function show(User $user)
    {
        $user->load(['group', 'speciality', 'usersTestsResults']);

        return Inertia::render('Admin/Student/Show', [
            'student' => $user,
            'results' => $user->usersTestsResults
        ]);
    }

    public function showResult(User $user, $moduleId)
    {
        $module = \App\Models\Module::with(['tests.options'])->findOrFail($moduleId);

        $answers = \App\Models\SolveTest::where('user_id', $user->id)
            ->where('module_id', $moduleId)
            ->get()
            ->groupBy('test_id');

        $result = $user->usersTestsResults()
            ->where('module_id', $moduleId)
            ->first();

        return Inertia::render('Admin/Student/Result', [
            'student' => $user,
            'module' => $module,
            'answers' => $answers,
            'diagnosis' => $result ? $result->pivot->result_real : null
        ]);
    }

    public function updateDiagnosis(Request $request, User $user, $moduleId)
    {
        $request->validate([
            'diagnosis' => 'nullable|string'
        ]);

        $user->usersTestsResults()->updateExistingPivot($moduleId, [
            'diagnosis' => $request->diagnosis,
            'result_real' => $request->diagnosis
        ]);

        return to_route('admin.students.index')->with('success', 'Diagnostika muvaffaqiyatli saqlandi');
    }
}
