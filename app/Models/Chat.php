<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'sub_type',
        'description',
        'location',
        'phone',
        'email',
        'image',
        'status',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'chat_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
