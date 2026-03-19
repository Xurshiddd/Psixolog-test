<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Student\SubmitModuleRequest;
use App\Http\Resources\Student\StudentModuleDetailResource;
use App\Http\Resources\Student\StudentModuleResultResource;
use App\Http\Resources\Student\StudentModuleSummaryResource;
use App\Models\Module;
use App\Services\StudentTestServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $modules = Module::query()
            ->where('is_active', true)
            ->withCount('tests')
            ->with([
                'usersTestsResults' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => StudentModuleSummaryResource::collection($modules),
        ]);
    }

    public function show(Request $request, Module $module): JsonResponse
    {
        abort_unless($module->is_active, 404);

        $module->load([
            'tests.options',
            'usersTestsResults' => fn ($query) => $query->where('user_id', $request->user()->id),
        ])->loadCount('tests');

        return response()->json([
            'data' => new StudentModuleDetailResource($module),
        ]);
    }

    public function submit(
        SubmitModuleRequest $request,
        Module $module,
        StudentTestServices $studentTestServices,
    ): JsonResponse {
        abort_unless($module->is_active, 404);

        $user = $request->user();

        if ($user->usersTestsResults()->whereKey($module->id)->exists()) {
            return response()->json([
                'message' => 'Siz bu testni avval topshirgansiz.',
            ], 422);
        }

        $response = $studentTestServices->processBatchSubmission(
            $user->id,
            $module->id,
            $request->validated('answers')
        );

        if (($response['status'] ?? 'error') !== 'success') {
            return response()->json([
                'message' => $response['message'] ?? 'Testni yuborishda xatolik yuz berdi.',
            ], 500);
        }

        $result = $user->usersTestsResults()
            ->withCount('tests')
            ->whereKey($module->id)
            ->firstOrFail();

        return response()->json([
            'message' => $response['message'] ?? 'Test muvaffaqiyatli yuborildi.',
            'data' => new StudentModuleResultResource($result),
        ], 201);
    }

    public function results(Request $request): JsonResponse
    {
        $results = $request->user()
            ->usersTestsResults()
            ->withCount('tests')
            ->orderBy('users_tests_results.created_at', 'desc')
            ->get();

        return response()->json([
            'data' => StudentModuleResultResource::collection($results),
        ]);
    }
}
