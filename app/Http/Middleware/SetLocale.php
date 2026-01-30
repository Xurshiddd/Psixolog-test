<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie('locale', config('app.locale', 'uz'));

        // faqat ruxsat berilgan tillar:
        $allowed = ['uz', 'ru'];
        if (! in_array($locale, $allowed, true)) {
            $locale = config('app.locale', 'uz');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
