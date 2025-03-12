<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'user_id',
        'admin_id',
        'message',
        'image',
        'is_admin',
    ];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }
}
