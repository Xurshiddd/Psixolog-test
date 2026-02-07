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
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get(['id', 'channel', 'subject', 'status', 'last_message_at', 'created_at']);

        $activeId = (int) $request->query('conversation');
        $activeConversation = null;
        $messages = [];

        if ($activeId) {
            $activeConversation = Conversation::where('student_id', $studentId)
                ->where('id', $activeId)
                ->first();

            if ($activeConversation) {
                $this->authorize('view', $activeConversation);

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

        return Inertia::render('Student/Requests/Index', [
            'conversations' => $conversations->map(fn ($c) => [
                'id' => $c->id,
                'channel' => $c->channel,
                'subject' => $c->subject,
                'status' => $c->status,
                'last_message_at' => optional($c->last_message_at)->toISOString(),
                'created_at' => $c->created_at->toISOString(),
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
