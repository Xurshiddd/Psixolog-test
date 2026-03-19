<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreConversationRequest;
use App\Http\Requests\Student\StoreMessageRequest;
use App\Http\Resources\Student\StudentConversationDetailResource;
use App\Http\Resources\Student\StudentConversationSummaryResource;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $studentId = $request->user()->id;

        $conversations = Conversation::query()
            ->where('student_id', $studentId)
            ->with([
                'latestMessage.sender:id,name',
            ])
            ->withCount([
                'messages as unread_count' => function ($query): void {
                    $query->where('sender_role', '!=', 'student')
                        ->whereNull('read_at');
                },
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => StudentConversationSummaryResource::collection($conversations),
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

        $conversation->load([
            'latestMessage.sender:id,name',
            'messages.sender:id,name',
        ])->loadCount([
            'messages as unread_count' => function ($query): void {
                $query->where('sender_role', '!=', 'student')
                    ->whereNull('read_at');
            },
        ]);

        return response()->json([
            'message' => $wasRecentlyCreated
                ? 'Yangi suhbat yaratildi.'
                : 'Bu kanal uchun mavjud suhbat ochildi.',
            'data' => new StudentConversationDetailResource($conversation),
        ], $wasRecentlyCreated ? 201 : 200);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->student_id === $request->user()->id, 403);

        $conversation->messages()
            ->where('sender_role', '!=', 'student')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $conversation->load([
            'latestMessage.sender:id,name',
            'messages' => fn ($query) => $query
                ->with('sender:id,name')
                ->orderBy('id'),
        ])->loadCount([
            'messages as unread_count' => function ($query): void {
                $query->where('sender_role', '!=', 'student')
                    ->whereNull('read_at');
            },
        ]);

        return response()->json([
            'data' => new StudentConversationDetailResource($conversation),
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

        $conversation->load([
            'latestMessage.sender:id,name',
            'messages' => fn ($query) => $query
                ->with('sender:id,name')
                ->orderBy('id'),
        ])->loadCount([
            'messages as unread_count' => function ($query): void {
                $query->where('sender_role', '!=', 'student')
                    ->whereNull('read_at');
            },
        ]);

        return response()->json([
            'message' => 'Xabar yuborildi.',
            'data' => new StudentConversationDetailResource($conversation),
        ], 201);
    }
}
