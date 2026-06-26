<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StudentMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && ! in_array(auth()->user()->role, ['student', 'employee', 'guest'], true)) {
            return redirect()->route('home')->with('error', 'Access denied. Test takers only area.');
        }
        return $next($request);
    }
}
