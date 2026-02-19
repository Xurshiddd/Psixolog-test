<?php

namespace App\Http\Controllers;

use App\Models\ResultCategory;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ResultCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = ResultCategory::with('module')->paginate(15);
        
        return Inertia::render('ResultCategories/Index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $modules = Module::all();
        
        return Inertia::render('ResultCategories/Create', [
            'modules' => $modules,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'diagnostic' => 'nullable|string',
            'fake_diagnostic' => 'nullable|string',
            'value' => 'required|integer',
            'module_id' => 'required|exists:modules,id',
        ]);

        $resultCategory = ResultCategory::where('module_id', $validated['module_id'])
            ->where('value', $validated['value'])
            ->first();

        if ($resultCategory) {
            throw ValidationException::withMessages([
                'value' => 'Bu modul uchun bu qiymat allaqachon mavjud.',
            ]);
        }

        ResultCategory::create($validated);

        return redirect()->route('result-categories.index')->with('success', 'Result Category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ResultCategory $resultCategory)
    {
        $resultCategory->load('module');
        
        return Inertia::render('ResultCategories/Show', [
            'category' => $resultCategory,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ResultCategory $resultCategory)
    {
        $modules = Module::all();
        
        return Inertia::render('ResultCategories/Edit', [
            'category' => $resultCategory,
            'modules' => $modules,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ResultCategory $resultCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'diagnostic' => 'nullable|string',
            'fake_diagnostic' => 'nullable|string',
            'value' => 'nullable|integer',
            'module_id' => 'required|exists:modules,id',
        ]);

        // Check for duplicate value in the same module (excluding current record)
        if ($validated['value'] && $validated['module_id']) {
            $existingCategory = ResultCategory::where('module_id', $validated['module_id'])
                ->where('value', $validated['value'])
                ->where('id', '!=', $resultCategory->id)
                ->first();

            if ($existingCategory) {
                throw ValidationException::withMessages([
                    'value' => 'Bu modul uchun bu qiymat allaqachon mavjud.',
                ]);
            }
        }

        $resultCategory->update($validated);

        return redirect()->route('result-categories.index')
                        ->with('success', 'Result Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ResultCategory $resultCategory)
    {
        $resultCategory->delete();

        return redirect()->route('result-categories.index')
                        ->with('success', 'Result Category deleted successfully.');
    }

    /**
     * Get distinct test option values for a module
     */
    public function getModuleTestOptions(Module $module)
    {
        $values = $module->tests()
                        ->with('options')
                        ->get()
                        ->flatMap(function ($test) {
                            return $test->options->pluck('option_value');
                        })
                        ->unique()
                        ->values()
                        ->sort();

        return response()->json($values);
    }
}
