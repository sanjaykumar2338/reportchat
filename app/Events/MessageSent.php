<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $message;

    public function __construct(ChatMessage $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new Channel('chat.' . $this->message->chat_id); // Broadcasting to a channel
    }

    public function broadcastWith()
    {
        return [
            'chat_id' => $this->message->chat_id,
            'message' => $this->message->message,
            'user_id' => $this->message->user_id,
            'is_admin' => (bool) $this->message->is_admin, 
            'image' => $this->message->image ? asset($this->message->image) : null,
            'created_at' => $this->message->created_at->format('Y-m-d H:i:s'),
        ];
    }
}