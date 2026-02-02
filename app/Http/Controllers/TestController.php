<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\TestStoreRequest;
use App\Http\Requests\TestUpdateRequest;
use App\Services\TestBuildServices;
use App\Models\Test;
use Illuminate\Support\Facades\Storage;

class TestController extends Controller
{
    public function __construct(protected TestBuildServices $testBuildServices)
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render("Tests", [
            'testsCount' => Test::count(),
            'modulesCount' => Module::count(),
            'modules' => Module::paginate(10),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('TestBuild');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TestStoreRequest $request)
    {
        $data = $request->validated();
        $this->testBuildServices->save($data);
        redirect()->route('test_index')->with('success', 'Test muvaffaqiyatli saqlandi!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $module = Module::with(['tests.options'])->findOrFail($id);
        return Inertia::render('EditTest', [
            'module' => $module
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TestUpdateRequest $request, string $id)
    {
        $module = Module::findOrFail($id);
        $data = $request->validated();
        $this->testBuildServices->update($module, $data);
        return redirect()->route('test_index')->with('success', 'Test muvaffaqiyatli yangilandi!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    public function changeModuleStatus(Request $request)
    {
        $request->validate([
            'module_id' => 'required|exists:modules,id',
            'field' => 'required|in:is_active,shuffle',
        ]);

        $module = Module::find($request->module_id);
        $field = $request->field;

        // Toggle the field
        $module->update([
            $field => !$module->{$field}
        ]);

        return redirect()->back()->with('success', 'Module status updated successfully.');
    }
    public function deleteModule($id)
    {
        $module = Module::find($id);
        $questioonImage = $module->tests()->whereNotNull('image')->get();
        foreach ($questioonImage as $question) {
            if ($question->image) {
                Storage::disk('public')->delete($question->image);
            }
        }
        $module->delete();
        return redirect()->back()->with('success', 'Module deleted successfully.');
    }
}
