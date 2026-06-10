<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'customer_name', 'status', 'notes', 'total_amount', 'handled_by', 'completed_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    const STATUS_PENDING   = 'pending';
    const STATUS_PREPARING = 'preparing';
    const STATUS_SERVING   = 'serving';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const STATUS_LABELS = [
        'pending'   => 'En attente',
        'preparing' => 'Préparation en cours',
        'serving'   => 'Service en cours',
        'completed' => 'Terminée',
        'cancelled' => 'Annulée',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled']);
    }
}
