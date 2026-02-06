<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;

class StartConversationController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'psychologist_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $student = $request->user();
        $psychologist = User::findOrFail($data['psychologist_id']);

        // role tekshiruvi (sizda qanday bo‘lsa shunday)
        abort_unless($psychologist->role === 'admin', 422, 'Not a psychologist');

        // 1:1 bo‘lsa oldin mavjud conversation bormi tekshirish:
        $existing = Conversation::query()
            ->whereHas('users', fn($q) => $q->where('users.id', $student->id))
            ->whereHas('users', fn($q) => $q->where('users.id', $psychologist->id))
            ->withCount('users')
            ->having('users_count', 2)
            ->first();

        $conversation = $existing ?? Conversation::create(['title' => 'Murojaat']);

        $conversation->users()->syncWithoutDetaching([$student->id, $psychologist->id]);

        return redirect()->route('chat.show', $conversation->id);
    }
}
