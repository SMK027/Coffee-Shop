<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'customer_name', 'loyalty_card_id', 'status', 'notes', 'total_amount',
        'handled_by', 'completed_at', 'points_credited', 'points_awarded',
    ];

    protected $casts = [
        'total_amount'    => 'decimal:2',
        'completed_at'    => 'datetime',
        'points_credited' => 'boolean',
        'points_awarded'  => 'integer',
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

    public function loyaltyCard(): BelongsTo
    {
        return $this->belongsTo(LoyaltyCard::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Crédite les points de fidélité sur la carte raccordée à la commande.
     *
     * À appeler une fois la commande marquée comme terminée. Chaque euro
     * dépensé donne lieu à un crédit (ratio configurable par les super admins).
     * Sécurisé contre le double-crédit via le champ points_credited.
     */
    public function creditLoyaltyPoints(): void
    {
        if ($this->points_credited || !$this->loyalty_card_id) {
            return;
        }

        $ratio  = \App\Models\Setting::pointsPerEuro();
        $points = (int) floor((float) $this->total_amount) * $ratio;

        $this->loyaltyCard()->increment('points', $points);

        $this->forceFill([
            'points_credited' => true,
            'points_awarded'  => $points,
        ])->save();
    }
}
