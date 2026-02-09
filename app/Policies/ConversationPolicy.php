<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ConversationPolicy
{
   public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->student_id === $user->id;
    }

    public function update(User $user, Conversation $conversation): bool
    {
        return $conversation->student_id === $user->id;
    }
    public function viewAsStaff(User $user, Conversation $conversation): bool
    {
        if (!in_array($user->role, ['admin', 'psiholog'], true)) {
            return false;
        }
        if ($conversation->channel !== $user->role) {
            return false;
        }
        return $conversation->staff_id === null || $conversation->staff_id === $user->id;
    }
}
