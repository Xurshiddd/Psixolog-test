<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StudentApiAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextToken = $request->bearerToken();

        if (blank($plainTextToken)) {
            return $this->unauthorizedResponse('Token topilmadi.');
        }

        $user = User::query()
            ->where('api_token', hash('sha256', $plainTextToken))
            ->first();

        if (! $user) {
            return $this->unauthorizedResponse('Token noto‘g‘ri yoki eskirgan.');
        }

        if ($user->role !== 'student') {
            return response()->json([
                'message' => 'Faqat student foydalanuvchilar kirishi mumkin.',
            ], 403);
        }

        $user->forceFill([
            'api_token_last_used_at' => now(),
        ])->save();

        auth()->setUser($user);
        $request->setUserResolver(fn (): User => $user);

        return $next($request);
    }

    protected function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], 401);
    }
}
