<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Module;

class StudentController extends Controller
{
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
}
