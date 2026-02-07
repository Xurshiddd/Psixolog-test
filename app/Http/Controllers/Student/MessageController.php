<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Events\MessageCreated;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    public function store(StoreMessageRequest $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_role' => 'student',
            'sender_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        $conversation->forceFill([
            'last_message_at' => $message->created_at,
        ])->save();
        // event(new MessageCreated($message));
        MessageCreated::dispatch($message);
        Log::info('Message created', [
            'message' => $message,
        ]);

        return redirect()->route('student.requests.index', ['conversation' => $conversation->id]);
    }
}
