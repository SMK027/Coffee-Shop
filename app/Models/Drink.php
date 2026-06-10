<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Drink extends Model
{
    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'price', 'image', 'available', 'sort_order',
    ];

    protected $casts = [
        'available' => 'boolean',
        'price'     => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(DrinkCategory::class, 'category_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('available', true);
    }
}
