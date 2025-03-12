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
        'description',
        'location',
        'phone',
        'email',
        'images',
        'status',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }
}
