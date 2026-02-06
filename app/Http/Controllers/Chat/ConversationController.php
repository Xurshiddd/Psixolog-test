<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Conversation;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $conversations = $user->conversations()
            ->with(['users:id,name', 'messages' => fn($q) => $q->latest()->limit(1)])
            ->latest('conversations.updated_at')
            ->get()
            ->map(function ($c) use ($user) {
                $last = $c->messages->first();
                $pivot = $c->users->firstWhere('id', $user->id)?->pivot;
                return [
                    'id' => $c->id,
                    'title' => $c->title,
                    'users' => $c->users->map(fn($u) => ['id'=>$u->id,'name'=>$u->name]),
                    'last_message' => $last ? [
                        'body' => $last->body,
                        'created_at' => $last->created_at?->toISOString(),
                        'sender_id' => $last->sender_id,
                    ] : null,
                    'last_read_at' => $pivot?->last_read_at?->toISOString(),
                ];
            });

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
        ]);
    }

    public function show(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->latest()
            ->paginate(30);

        return Inertia::render('Chat/Show', [
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'users' => $conversation->users()->select('users.id','users.name')->get(),
            ],
            // paginate reverse bo‘lgani uchun frontendda unshift yoki reverse qilasiz
            'messages' => $messages,
        ]);
    }

    public function markRead(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $conversation->users()->updateExistingPivot($request->user()->id, [
            'last_read_at' => now(),
        ]);

        return back();
    }
}
