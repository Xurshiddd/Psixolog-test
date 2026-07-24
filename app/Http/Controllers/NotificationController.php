<?php

namespace App\Http\Controllers;

use App\Models\User;

class NotificationController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return response()->json([
            'notifications' => $user->unreadNotifications,
        ]);
    }

    public function markAsRead($id)
    {
        $user = auth()->user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        $targetId = $notification->data['student_id'] ?? null;
        $moduleId = $notification->data['module_id'] ?? null;

        if (! $targetId) {
            return redirect()->back();
        }

        // Foydalanuvchi roliga qarab to'g'ri bo'limga yo'naltiramiz.
        // Rolni bazadan olamiz — eski bildirishnomalarda `role` bo'lmasligi mumkin.
        $role = User::query()->whereKey($targetId)->value('role')
            ?? ($notification->data['role'] ?? 'student');

        $prefix = match ($role) {
            'employee' => 'admin.employees',
            'guest' => 'admin.guests',
            default => 'admin.students',
        };

        if ($moduleId) {
            return redirect()->route("{$prefix}.results.show", ['user' => $targetId, 'module' => $moduleId]);
        }

        return redirect()->route("{$prefix}.show", ['user' => $targetId]);
    }

    public function markAllAsRead()
    {
        $user = auth()->user();
        $user->unreadNotifications->markAsRead();

        return redirect()->back();
    }
}
