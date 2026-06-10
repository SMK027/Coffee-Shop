<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'drink_id', 'quantity', 'unit_price'];

    protected $casts = [
        'unit_price' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function drink(): BelongsTo
    {
        return $this->belongsTo(Drink::class);
    }

    public function getSubtotalAttribute(): float
    {
        return $this->quantity * $this->unit_price;
    }
}
