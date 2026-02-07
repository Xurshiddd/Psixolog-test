<?php

namespace App\Http\Controllers\Staff;

use App\Events\MessageCreated;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class MessagesController extends Controller
{
    public function adminStore(Request $request, Conversation $conversation)
    {
        return $this->storeForChannel($request, $conversation, 'admin');
    }

    public function psihologStore(Request $request, Conversation $conversation)
    {
        return $this->storeForChannel($request, $conversation, 'psiholog');
    }

    private function storeForChannel(Request $request, Conversation $conversation, string $channel)
    {
        $user = $request->user();

        // role mos bo'lishi shart
        abort_unless($user->role === $channel, 403);

        // conversation channel mos bo'lishi shart
        abort_unless($conversation->channel === $channel, 403);

        $this->authorize('viewAsStaff', $conversation);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        $msg = Message::create([
            'conversation_id' => $conversation->id,
            'sender_role' => 'staff',
            'sender_id' => $user->id,
            'body' => $data['body'],
        ]);

        $conversation->forceFill([
            'last_message_at' => $msg->created_at,
            'staff_id' => $conversation->staff_id ?? $user->id,
        ])->save();

        // event(new MessageCreated($msg));
        MessageCreated::dispatch($msg);

        return redirect()->route("{$channel}.requests.index", [
            'student' => $conversation->student_id,
        ]);
    }
}
