<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

        $studentId = $notification->data['student_id'] ?? null;
        $moduleId = $notification->data['module_id'] ?? null;

        if ($studentId && $moduleId) {
            return redirect()->route('admin.students.results.show', ['user' => $studentId, 'module' => $moduleId]);
        } elseif ($studentId) {
            return redirect()->route('admin.students.show', ['user' => $studentId]);
        }

        return redirect()->back();
    }

    public function markAllAsRead()
    {
        $user = auth()->user();
        $user->unreadNotifications->markAsRead();
        return redirect()->back();
    }
}
