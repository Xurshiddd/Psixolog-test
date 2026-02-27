<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreConversationRequest;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $studentId = $request->user()->id;

        $conversations = Conversation::query()
            ->where('student_id', $studentId)
            ->withCount(['messages as unread_count' => function ($query) {
                $query->where('sender_role', '!=', 'student')
                      ->whereNull('read_at');
            }])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get(['id', 'channel', 'subject', 'status', 'last_message_at', 'created_at', 'unread_count']);

        $activeId = (int) $request->query('conversation');
        $activeConversation = null;
        $messages = [];

        if ($activeId) {
            $activeConversation = Conversation::where('student_id', $studentId)
                ->where('id', $activeId)
                ->first();

            if ($activeConversation) {
                $this->authorize('view', $activeConversation);

                // Mark messages from staff as read
                $activeConversation->messages()
                    ->where('sender_role', '!=', 'student')
                    ->whereNull('read_at')
                    ->update(['read_at' => now()]);

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
                        'created_at' => $m->created_at->format('d.m.Y H:i'),
                    ]);
            }
        }

        return Inertia::render('Student/Requests/Index', [
            'conversations' => $conversations->map(fn ($c) => [
                'id' => $c->id,
                'channel' => $c->channel,
                'subject' => $c->subject,
                'status' => $c->status,
                'unread_count' => $c->unread_count,
                'last_message_at' => optional($c->last_message_at)->format('d.m.Y H:i'),
                'created_at' => $c->created_at->format('d.m.Y H:i'),
            ]),
            'activeConversation' => $activeConversation ? [
                'id' => $activeConversation->id,
                'channel' => $activeConversation->channel,
                'subject' => $activeConversation->subject,
                'status' => $activeConversation->status,
            ] : null,
            'messages' => $messages,
        ]);
    }

    public function store(StoreConversationRequest $request)
    {
        $conversation = Conversation::create([
            'student_id' => $request->user()->id,
            'channel' => $request->validated('channel'),
            'subject' => $request->validated('subject'),
            'status' => 'open',
        ]);

        return redirect()->route('student.requests.index', ['conversation' => $conversation->id]);
    }
}