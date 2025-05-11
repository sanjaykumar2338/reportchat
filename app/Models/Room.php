<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'floor',
        'category',
        'image_url',
        'available_from',
        'available_to',
        'capacity',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
