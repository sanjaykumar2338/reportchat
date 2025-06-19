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
        'company',
        'image_url',
        'available_from',
        'available_to',
        'capacity',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class, 'company', 'id');
    }

    public function getImageUrlAttribute($value)
    {
        return $value ? asset('storage/' . ltrim($value, '/')) : null;
    }
}
