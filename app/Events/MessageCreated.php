<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageCreated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public Message $message) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('conversations.' . $this->message->conversation_id)];
    }

    public function broadcastAs(): string
    {
        return 'message.created';
    }

    public function broadcastWith(): array
    {
        $m = $this->message->loadMissing('sender:id,name');

        return [
            'id' => $m->id,
            'conversation_id' => $m->conversation_id,
            'sender_role' => $m->sender_role,
            'sender_id' => $m->sender_id,
            'sender_name' => $m->sender?->name,
            'body' => $m->body,
            'created_at' => $m->created_at->toISOString(),
        ];
    }
}
