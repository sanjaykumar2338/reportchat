<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceListing extends Model
{
    protected $table = 'marketplace_listings';

    protected $fillable = [
        'title',
        'description',
        'category_id',
        'price',
        'whatsapp',
        'is_active',
        'published_at',
        'ends_at',
        'user_id',
        'images'
    ];

    protected $casts = [
        'images' => 'array',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    // Relationship to category
    public function category()
    {
        return $this->belongsTo(MarketplaceCategory::class, 'category_id');
    }

    // Relationship to user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}