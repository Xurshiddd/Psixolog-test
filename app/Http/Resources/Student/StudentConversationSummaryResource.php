<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentConversationSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel' => $this->channel,
            'subject' => $this->subject,
            'status' => $this->status,
            'unread_count' => (int) ($this->unread_count ?? 0),
            'last_message_at' => $this->last_message_at,
            'created_at' => $this->created_at,
            'latest_message' => $this->whenLoaded('latestMessage', fn () => $this->latestMessage ? [
                'id' => $this->latestMessage->id,
                'sender_role' => $this->latestMessage->sender_role,
                'sender_name' => $this->latestMessage->sender?->name,
                'body' => $this->latestMessage->body,
                'created_at' => $this->latestMessage->created_at,
            ] : null),
        ];
    }
}
