<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceCategory extends Model
{
    protected $fillable = ['name', 'icon'];

    public function listings()
    
    {
        return $this->hasMany(MarketplaceListing::class, 'category_id');
    }

    public function category()
    {
        return $this->belongsTo(MarketplaceCategory::class, 'category_id');
    }
}