<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use App\Services\UnreadRequestsCountService;
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
            ->select('id', 'name', 'email')
            ->whereIn('role', ['student', 'employee', 'guest'])
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
            })
            ->addSelect(['unread_count' => \App\Models\Message::selectRaw('COUNT(*)')
                ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
                ->whereColumn('conversations.student_id', 'users.id')
                ->where('conversations.channel', $channel)
                ->where('messages.sender_role', 'student')
                ->whereNull('messages.read_at'),
            ])
            ->orderByDesc('unread_count')
            ->orderBy('name')
            ->limit(200)
            ->get();

        $activeStudentId = (int) $request->query('student');
        $activeStudent = null;
        $activeConversation = null;
        $messages = collect();

        if ($activeStudentId) {
            $activeStudent = User::query()->whereIn('role', ['student', 'employee', 'guest'])->whereKey($activeStudentId)->first(['id', 'name', 'email']);

            if ($activeStudent) {
                $activeConversation = Conversation::query()
                    ->where('student_id', $activeStudent->id)
                    ->where('channel', $channel)
                    ->first();

                if (! $activeConversation) {
                    $activeConversation = Conversation::create([
                        'student_id' => $activeStudent->id,
                        'channel' => $channel,
                        'status' => 'open',
                        'staff_id' => $user->id,
                    ]);
                } else {
                    // Mark messages from student as read
                    $activeConversation->messages()
                        ->where('sender_role', 'student')
                        ->whereNull('read_at')
                        ->update(['read_at' => now()]);
                    app(UnreadRequestsCountService::class)->forgetForConversation($activeConversation);
                }

                $this->authorize('viewAsStaff', $activeConversation);

                if ($activeConversation->staff_id === null) {
                    $activeConversation->forceFill(['staff_id' => $user->id])->save();
                }

                $messages = $activeConversation->messages()
                    ->with('sender:id,name')
                    ->orderBy('id')
                    ->get(['id', 'conversation_id', 'sender_role', 'sender_id', 'body', 'created_at'])
                    ->map(fn ($m) => [
                        'id' => $m->id,
                        'sender_role' => $m->sender_role,
                        'sender_id' => $m->sender_id,
                        'sender_name' => $m->sender?->name,
                        'body' => $m->body,
                        'created_at' => $m->created_at->format('d.m.Y H:i'),
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
