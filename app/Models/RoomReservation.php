<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomReservation extends Model
{
    protected $fillable = [
        'room_id', 'user_id', 'date', 'start_time', 'end_time', 'duration_minutes', 'status', 'repeat_option', 'all_day'
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
