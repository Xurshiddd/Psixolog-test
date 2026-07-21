<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Http\Middleware\AdminMidleware;
use App\Http\Middleware\StudentMiddleware;
use App\Http\Middleware\DoubleMiddleware;
use App\Http\Middleware\PsihologMiddleware;
use App\Http\Middleware\StudentApiAuthMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        channels: __DIR__.'/../routes/channels.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->alias([
            'admin' => AdminMidleware::class,
            'student' => StudentMiddleware::class,
            'psiholog' => PsihologMiddleware::class,
            'double' => DoubleMiddleware::class,
            'student.api' => StudentApiAuthMiddleware::class,
        ]);
        $middleware->web(append: [
            SetLocale::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sessiya (yoki CSRF token) eskirganda Laravel 419 "Page Expired" qaytaradi.
        // Logout uchun bu holat xato emas: sessiya allaqachon yo'q, ya'ni foydalanuvchi
        // chiqib bo'lgan. Shuning uchun 419 o'rniga uni bosh sahifaga yuboramiz.
        // Eslatma: TokenMismatchException Handler ichida HttpException(419) ga
        // aylantirilgani uchun shu yerda status bo'yicha tekshiramiz.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            if ($request->routeIs('logout')) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('home');
            }

            return back()->with('error', 'Sessiya muddati tugadi. Iltimos, qaytadan urinib ko\'ring.');
        });
    })->create();
