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
        // role: admin/psiholog bo'lishi kerak
        if (!in_array($user->role, ['admin', 'psiholog'], true)) {
            return false;
        }

        // channel mos bo'lishi kerak
        if ($conversation->channel !== $user->role) {
            return false;
        }

        // staff_id null bo'lsa: birinchi kirgan staff "egallaydi" (controllerda set qilamiz)
        // staff_id bo'lsa: faqat o'sha staff ko'radi
        return $conversation->staff_id === null || $conversation->staff_id === $user->id;
    }
}
