<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

Broadcast::channel('conversations.{conversation}', function ($user, Conversation $conversation) {
    // Student o'z chatini ko'rsin
    if ($conversation->student_id === $user->id) {
        return ['id' => $user->id, 'name' => $user->name];
    }

    // Staff: admin yoki psiholog, channel mos bo'lsa, staff_id null yoki o'zi bo'lsa
    if (in_array($user->role, ['admin', 'psiholog'], true)
        && $conversation->channel === $user->role
        && ($conversation->staff_id === null || $conversation->staff_id === $user->id)
    ) {
        return ['id' => $user->id, 'name' => $user->name];
    }

    return false;
});