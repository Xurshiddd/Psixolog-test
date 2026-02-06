<?php

namespace App\Http\Controllers\Chat;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function store(Request $request, Conversation $conversation)
    {
        $this->authorize('sendMessage', $conversation);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $conversation->touch();

        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'ok' => true,
            'message' => [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'body' => $message->body,
                'created_at' => $message->created_at?->toISOString(),
                'sender' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                ],
            ],
        ]);
    }
}
