<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreConversationRequest;
use App\Models\Conversation;
use App\Services\UnreadRequestsCountService;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConversationController extends Controller
{
    private const CONVERSATIONS_PER_PAGE = 20;

    private const MESSAGES_PER_PAGE = 50;

    public function index(Request $request)
    {
        $studentId = $request->user()->id;

        $conversations = $this->conversationListQuery($studentId)
            ->simplePaginate(
                self::CONVERSATIONS_PER_PAGE,
                ['id', 'student_id', 'channel', 'subject', 'status', 'last_message_at', 'created_at'],
                'conversations_page'
            )
            ->withQueryString();

        $activeId = (int) $request->query('conversation');
        $activeConversation = null;
        $messages = [];
        $messagesPagination = null;

        if ($activeId) {
            $activeConversation = Conversation::query()
                ->select(['id', 'student_id', 'channel', 'subject', 'status', 'last_message_at', 'created_at'])
                ->where('student_id', $studentId)
                ->where('id', $activeId)
                ->first();

            if ($activeConversation) {
                $this->authorize('view', $activeConversation);

                // Mark messages from staff as read
                $activeConversation->messages()
                    ->where('sender_role', '!=', 'student')
                    ->whereNull('read_at')
                    ->update(['read_at' => now()]);
                app(UnreadRequestsCountService::class)->forgetForConversation($activeConversation);

                $messagesPaginator = $activeConversation->messages()
                    ->select(['id', 'conversation_id', 'sender_role', 'sender_id', 'body', 'read_at', 'created_at'])
                    ->with('sender:id,name')
                    ->orderByDesc('id')
                    ->simplePaginate(
                        self::MESSAGES_PER_PAGE,
                        ['id', 'conversation_id', 'sender_role', 'sender_id', 'body', 'read_at', 'created_at'],
                        'messages_page'
                    )
                    ->withQueryString();

                $messages = $messagesPaginator->getCollection()
                    ->reverse()
                    ->values()
                    ->map(fn ($m) => [
                        'id' => $m->id,
                        'sender_role' => $m->sender_role,
                        'sender_id' => $m->sender_id,
                        'sender_name' => $m->sender?->name,
                        'body' => $m->body,
                        'created_at' => $m->created_at->format('d.m.Y H:i'),
                    ])
                    ->all();

                $messagesPagination = $this->paginationMeta($messagesPaginator);
            }
        }

        return Inertia::render('Student/Requests/Index', [
            'conversations' => $conversations->getCollection()->map(fn ($c) => [
                'id' => $c->id,
                'channel' => $c->channel,
                'subject' => $c->subject,
                'status' => $c->status,
                'unread_count' => $c->unread_count,
                'last_message_at' => optional($c->last_message_at)->format('d.m.Y H:i'),
                'created_at' => $c->created_at->format('d.m.Y H:i'),
                'latest_message' => $c->latestMessage ? [
                    'id' => $c->latestMessage->id,
                    'sender_role' => $c->latestMessage->sender_role,
                    'sender_name' => $c->latestMessage->sender?->name,
                    'body' => $c->latestMessage->body,
                    'created_at' => optional($c->latestMessage->created_at)->format('d.m.Y H:i'),
                ] : null,
            ])->values()->all(),
            'conversationsPagination' => $this->paginationMeta($conversations),
            'activeConversation' => $activeConversation ? [
                'id' => $activeConversation->id,
                'channel' => $activeConversation->channel,
                'subject' => $activeConversation->subject,
                'status' => $activeConversation->status,
            ] : null,
            'messages' => $messages,
            'messagesPagination' => $messagesPagination,
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

    private function conversationListQuery(int $studentId)
    {
        return Conversation::query()
            ->where('student_id', $studentId)
            ->with([
                'latestMessage' => fn ($query) => $query
                    ->select([
                        'messages.id',
                        'messages.conversation_id',
                        'messages.sender_role',
                        'messages.sender_id',
                        'messages.body',
                        'messages.created_at',
                    ])
                    ->with('sender:id,name'),
            ])
            ->withCount(['messages as unread_count' => function ($query) {
                $query->where('sender_role', '!=', 'student')
                    ->whereNull('read_at');
            }])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');
    }

    private function paginationMeta(Paginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_page_url' => $paginator->previousPageUrl(),
            'has_more_pages' => $paginator->hasMorePages(),
        ];
    }
}
