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

class AdminStudentController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'student')
            ->with(['group', 'speciality', 'usersTestsResults']);

        // Filter by group
        if ($request->has('group_id') && $request->group_id) {
            $query->where('group_id', $request->group_id);
        }

        // Filter by speciality
        if ($request->has('speciality_id') && $request->speciality_id) {
            $query->where('speciality_id', $request->speciality_id);
        }

        
        if ($request->has('test_status') && $request->test_status) {
            if ($request->test_status === 'submitted') {
                $query->whereHas('usersTestsResults');
            } elseif ($request->test_status === 'not_submitted') {
                $query->whereDoesntHave('usersTestsResults');
            }
        }

        $students = $query->latest()->paginate(10);

        // Get all groups and specialities for filter dropdowns
        $groups = Group::orderBy('name')->get();
        $specialities = Speciality::orderBy('name')->get();

        return Inertia::render('Admin/Student/Index', [
            'students' => $students,
            'groups' => $groups,
            'specialities' => $specialities,
            'filters' => [
                'group_id' => $request->get('group_id'),
                'speciality_id' => $request->get('speciality_id'),
                'test_status' => $request->get('test_status'),
            ]
        ]);
    }

    public function show(Request $request, User $user)
    {
        $user->load(['group', 'speciality', 'usersTestsResults']);

        return Inertia::render('Admin/Student/Show', [
            'student' => $user,
            'results' => $user->usersTestsResults,
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
        $module = Module::with(['tests.options'])->findOrFail($moduleId);

        $answers = SolveTest::where('user_id', $user->id)
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

    private function getFilteredStudents(Request $request)
    {
        $query = User::where('role', 'student')
            ->with(['group', 'speciality', 'usersTestsResults']);

        // Filter by group
        if ($request->has('group_id') && $request->group_id) {
            $query->where('group_id', $request->group_id);
        }

        // Filter by speciality
        if ($request->has('speciality_id') && $request->speciality_id) {
            $query->where('speciality_id', $request->speciality_id);
        }

        // Filter by test status
        if ($request->has('test_status') && $request->test_status) {
            if ($request->test_status === 'submitted') {
                $query->whereHas('usersTestsResults');
            } elseif ($request->test_status === 'not_submitted') {
                $query->whereDoesntHave('usersTestsResults');
            }
        }

        return $query->latest()->get();
    }

    public function exportExcel(Request $request)
    {
        $students = $this->getFilteredStudents($request);
        $timestamp = now()->format('Y-m-d_H-i-s');
        return Excel::download(new StudentsExport($students), "talabalar_$timestamp.xlsx");
    }

    public function exportPdf(Request $request, StudentPdfExportService $pdfExportService)
    {
        $students = $this->getFilteredStudents($request);
        $timestamp = now()->format('Y-m-d_H-i-s');
        $pdf = $pdfExportService->generatePdf($students);
        return $pdf->download("talabalar_$timestamp.pdf");
    }

}