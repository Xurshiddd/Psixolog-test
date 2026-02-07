<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RequestsController extends Controller
{
    public function adminIndex(Request $request)
    {
        return $this->indexForChannel($request, 'admin');
    }

    public function psihologIndex(Request $request)
    {
        return $this->indexForChannel($request, 'psiholog');
    }

    private function indexForChannel(Request $request, string $channel)
    {
        $user = $request->user();
        abort_unless($user->role === $channel, 403);

        $q = trim((string) $request->query('q', ''));

        $students = User::query()
            ->students()
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
            })
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'email']);

        $activeStudentId = (int) $request->query('student');
        $activeStudent = null;
        $activeConversation = null;
        $messages = collect();

        if ($activeStudentId) {
            $activeStudent = User::query()->students()->whereKey($activeStudentId)->first(['id', 'name', 'email']);

            if ($activeStudent) {
                $activeConversation = Conversation::query()
                    ->where('student_id', $activeStudent->id)
                    ->where('channel', $channel)
                    ->first();

                if (!$activeConversation) {
                    $activeConversation = Conversation::create([
                        'student_id' => $activeStudent->id,
                        'channel' => $channel,
                        'status' => 'open',
                        'staff_id' => $user->id,
                    ]);
                }

                $this->authorize('viewAsStaff', $activeConversation);

                if ($activeConversation->staff_id === null) {
                    $activeConversation->forceFill(['staff_id' => $user->id])->save();
                }

                $messages = $activeConversation->messages()
                    ->with('sender:id,name')
                    ->orderBy('id')
                    ->get(['id','conversation_id','sender_role','sender_id','body','created_at'])
                    ->map(fn ($m) => [
                        'id' => $m->id,
                        'sender_role' => $m->sender_role,
                        'sender_id' => $m->sender_id,
                        'sender_name' => $m->sender?->name,
                        'body' => $m->body,
                        'created_at' => $m->created_at->toISOString(),
                    ]);
            }
        }

        return Inertia::render('Staff/Requests/Index', [
            'channel' => $channel,
            'q' => $q,
            'students' => $students,
            'activeStudent' => $activeStudent,
            'activeConversation' => $activeConversation ? [
                'id' => $activeConversation->id,
                'status' => $activeConversation->status,
                'subject' => $activeConversation->subject,
            ] : null,
            'messages' => $messages,
        ]);
    }
}
