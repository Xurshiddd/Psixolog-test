<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreConversationRequest;
use App\Http\Requests\Student\StoreMessageRequest;
use App\Http\Resources\Student\StudentConversationDetailResource;
use App\Http\Resources\Student\StudentConversationSummaryResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\UnreadRequestsCountService;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    private const CONVERSATIONS_PER_PAGE = 20;

    private const MESSAGES_PER_PAGE = 50;

    public function index(Request $request): JsonResponse
    {
        $studentId = $request->user()->id;

        $conversations = $this->conversationListQuery($studentId)
            ->simplePaginate(
                self::CONVERSATIONS_PER_PAGE,
                ['id', 'student_id', 'channel', 'subject', 'status', 'last_message_at', 'created_at'],
                'conversations_page'
            );

        return response()->json([
            'data' => StudentConversationSummaryResource::collection($conversations->getCollection())->resolve(),
            'meta' => [
                'conversations' => $this->paginationMeta($conversations),
            ],
        ]);
    }

    public function store(StoreConversationRequest $request): JsonResponse
    {
        $conversation = Conversation::query()
            ->where('student_id', $request->user()->id)
            ->where('channel', $request->validated('channel'))
            ->orderBy('id')
            ->first();

        $wasRecentlyCreated = false;

        if (! $conversation) {
            $conversation = Conversation::create([
                'student_id' => $request->user()->id,
                'channel' => $request->validated('channel'),
                'subject' => $request->validated('subject'),
                'status' => 'open',
            ]);
            $wasRecentlyCreated = true;
        } elseif (blank($conversation->subject) && filled($request->validated('subject'))) {
            $conversation->forceFill([
                'subject' => $request->validated('subject'),
            ])->save();
        }

        $messagesPaginator = $this->messagePaginator($conversation);
        $this->hydrateConversationDetail($conversation, $messagesPaginator);

        return response()->json([
            'message' => $wasRecentlyCreated
                ? 'Yangi suhbat yaratildi.'
                : 'Bu kanal uchun mavjud suhbat ochildi.',
            'data' => new StudentConversationDetailResource($conversation),
            'meta' => [
                'messages' => $this->paginationMeta($messagesPaginator),
            ],
        ], $wasRecentlyCreated ? 201 : 200);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->student_id === $request->user()->id, 403);

        $conversation->messages()
            ->where('sender_role', '!=', 'student')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        app(UnreadRequestsCountService::class)->forgetForConversation($conversation);

        $messagesPaginator = $this->messagePaginator($conversation);
        $this->hydrateConversationDetail($conversation, $messagesPaginator);

        return response()->json([
            'data' => new StudentConversationDetailResource($conversation),
            'meta' => [
                'messages' => $this->paginationMeta($messagesPaginator),
            ],
        ]);
    }

    public function storeMessage(
        StoreMessageRequest $request,
        Conversation $conversation,
    ): JsonResponse {
        abort_unless($conversation->student_id === $request->user()->id, 403);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_role' => 'student',
            'sender_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        $conversation->forceFill([
            'last_message_at' => $message->created_at,
        ])->save();

        $messagesPaginator = $this->messagePaginator($conversation);
        $this->hydrateConversationDetail($conversation, $messagesPaginator);

        return response()->json([
            'message' => 'Xabar yuborildi.',
            'data' => new StudentConversationDetailResource($conversation),
            'meta' => [
                'messages' => $this->paginationMeta($messagesPaginator),
            ],
        ], 201);
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
            ->withCount([
                'messages as unread_count' => function ($query): void {
                    $query->where('sender_role', '!=', 'student')
                        ->whereNull('read_at');
                },
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');
    }

    private function messagePaginator(Conversation $conversation)
    {
        return $conversation->messages()
            ->select(['id', 'conversation_id', 'sender_role', 'sender_id', 'body', 'read_at', 'created_at'])
            ->with('sender:id,name')
            ->orderByDesc('id')
            ->simplePaginate(
                self::MESSAGES_PER_PAGE,
                ['id', 'conversation_id', 'sender_role', 'sender_id', 'body', 'read_at', 'created_at'],
                'messages_page'
            );
    }

    private function hydrateConversationDetail(Conversation $conversation, Paginator $messagesPaginator): void
    {
        $conversation->load([
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
        ])->loadCount([
            'messages as unread_count' => function ($query): void {
                $query->where('sender_role', '!=', 'student')
                    ->whereNull('read_at');
            },
        ]);

        $conversation->setRelation(
            'messages',
            $messagesPaginator->getCollection()->reverse()->values()
        );
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
