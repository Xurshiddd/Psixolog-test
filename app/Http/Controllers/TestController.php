<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\TestStoreRequest;
use App\Services\TestBuildServices;
use App\Models\Test;

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
            'modules' => Module::count(),
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
        redirect()->route('test_index')->with('success','Test muvaffaqiyatli saqlandi!');
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
