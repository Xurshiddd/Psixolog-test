<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }


    public function share(Request $request): array
    {
        $user = $request->user();
        $unreadRequestsCount = 0;

        if ($user) {
            if ($user->role === 'student') {
                $unreadRequestsCount = \App\Models\Message::whereHas('conversation', function ($query) use ($user) {
                    $query->where('student_id', $user->id);
                })
                    ->where('sender_role', '!=', 'student')
                    ->whereNull('read_at')
                    ->count();
            }
            else if (in_array($user->role, ['admin', 'psiholog'])) {
                $unreadRequestsCount = \App\Models\Message::whereHas('conversation', function ($query) use ($user) {
                    $query->where('channel', $user->role);
                })
                    ->where('sender_role', 'student')
                    ->whereNull('read_at')
                    ->count();
            }
        }

        return array_merge(parent::share($request), [
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'unread_requests_count' => $unreadRequestsCount,
            'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'locale' => fn() => app()->getLocale(),
            'flash' => [
                'status' => fn() => $request->session()->get('status'),
                'message' => fn() => $request->session()->get('message'),
                'results' => fn() => $request->session()->get('results'),
                'error' => fn() => $request->session()->get('error'),
                'success' => fn() => $request->session()->get('success'),
            ],
        ]);
    }
}