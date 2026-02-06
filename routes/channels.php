<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

Broadcast::channel('conversations.{conversation}', function ($user, Conversation $conversation) {
    return $conversation->users()->whereKey($user->id)->exists();
});