<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Student\LoginRequest;
use App\Http\Resources\Student\StudentProfileResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('login', (int) $request->input('login'))
            ->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            return response()->json([
                'message' => 'Login yoki parol noto‘g‘ri.',
            ], 422);
        }

        if ($user->role !== 'student') {
            return response()->json([
                'message' => 'Faqat student foydalanuvchilar kirishi mumkin.',
            ], 403);
        }

        $token = $user->issueApiToken();
        $user->load(['group', 'speciality', 'faculity', 'usersCategory']);

        return response()->json([
            'message' => 'Muvaffaqiyatli kirildi.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => new StudentProfileResource($user),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['group', 'speciality', 'faculity', 'usersCategory']);

        return response()->json([
            'data' => new StudentProfileResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->revokeApiToken();

        return response()->json([
            'message' => 'Tizimdan chiqildi.',
        ]);
    }
}
