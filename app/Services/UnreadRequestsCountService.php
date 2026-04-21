<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UnreadRequestsCountService
{
    private const CACHE_TTL_SECONDS = 60;

    public function getForUser(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        if ($user->role === 'student') {
            return (int) Cache::remember(
                $this->studentCacheKey($user->id),
                now()->addSeconds(self::CACHE_TTL_SECONDS),
                fn (): int => $this->countForStudent($user->id)
            );
        }

        if (in_array($user->role, ['admin', 'psiholog'], true)) {
            return (int) Cache::remember(
                $this->channelCacheKey($user->role),
                now()->addSeconds(self::CACHE_TTL_SECONDS),
                fn (): int => $this->countForChannel($user->role)
            );
        }

        return 0;
    }

    public function forgetForConversation(Conversation $conversation): void
    {
        $studentId = (int) $conversation->student_id;
        $channel = $conversation->channel;

        if ($studentId > 0) {
            Cache::forget($this->studentCacheKey($studentId));
        }

        if (filled($channel)) {
            Cache::forget($this->channelCacheKey((string) $channel));
        }
    }

    public function forgetForStudentAndChannel(int $studentId, ?string $channel): void
    {
        if ($studentId > 0) {
            Cache::forget($this->studentCacheKey($studentId));
        }

        if (filled($channel)) {
            Cache::forget($this->channelCacheKey((string) $channel));
        }
    }

    private function countForStudent(int $studentId): int
    {
        return Message::query()
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->where('conversations.student_id', $studentId)
            ->where('messages.sender_role', '!=', 'student')
            ->whereNull('messages.read_at')
            ->count();
    }

    private function countForChannel(string $channel): int
    {
        return Message::query()
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->where('conversations.channel', $channel)
            ->where('messages.sender_role', 'student')
            ->whereNull('messages.read_at')
            ->count();
    }

    private function studentCacheKey(int $studentId): string
    {
        return "chat:unread-requests-count:student:{$studentId}";
    }

    private function channelCacheKey(string $channel): string
    {
        return "chat:unread-requests-count:channel:{$channel}";
    }
}
