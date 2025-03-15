<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
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
            'message' => $this->message->message,
            'user_id' => $this->message->user_id,
            'image' => $this->message->image,
        ];
    }
}