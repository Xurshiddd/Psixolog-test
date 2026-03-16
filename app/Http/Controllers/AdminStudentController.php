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
        $module = Module::with(['tests.options'])->findOrFail($moduleId);

        $answers = SolveTest::where('user_id', $user->id)
            ->where('module_id', $moduleId)
            ->get()
            ->groupBy('test_id');

        $result = $user->usersTestsResults()
            ->where('module_id', $moduleId)
            ->first();

        // If psychologist has provided a diagnosis use it, otherwise fall back to generated result_real
        $diagnosisValue = null;
        if ($result) {
            $diagnosisValue = $result->pivot->diagnosis;
        }

        return Inertia::render('Admin/Student/Result', [
            'student' => $user,
            'module' => $module,
            'answers' => $answers,
            'diagnosis' => $diagnosisValue
        ]);
    }

    public function updateDiagnosis(Request $request, User $user, $moduleId)
    {
        $request->validate([
            'diagnosis' => 'nullable|string'
        ]);

        $user->usersTestsResults()->updateExistingPivot($moduleId, [
            'diagnosis' => $request->diagnosis,
        ]);

        return redirect()->back()->with('success', 'Diagnostika muvaffaqiyatli saqlandi');
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