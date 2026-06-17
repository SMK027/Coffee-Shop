<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'customer_name', 'loyalty_card_id', 'is_employee_order', 'status', 'notes',
        'total_amount', 'discount_amount', 'loyalty_points_spent', 'loyalty_discount_amount', 'handled_by', 'completed_at',
        'points_credited', 'points_awarded',
    ];

    protected $casts = [
        'total_amount'      => 'decimal:2',
        'discount_amount'   => 'decimal:2',
        'loyalty_discount_amount' => 'decimal:2',
        'is_employee_order' => 'boolean',
        'loyalty_points_spent' => 'integer',
        'completed_at'      => 'datetime',
        'points_credited'   => 'boolean',
        'points_awarded'    => 'integer',
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

    /** Taux de réduction immédiate accordé sur les commandes des salariés. */
    const EMPLOYEE_DISCOUNT_RATE = 0.15;

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

    public function loyaltyDiscounts(): BelongsToMany
    {
        return $this->belongsToMany(LoyaltyDiscount::class, 'order_loyalty_discounts')
            ->withPivot('points_spent', 'discount_amount')
            ->withTimestamps();
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->customer_name ?? 'Anonyme #' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
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

        if ($points > 0) {
            $balanceAfter = $this->loyaltyCard()->value('points');
            \App\Models\LoyaltyPointAdjustment::create([
                'loyalty_card_id' => $this->loyalty_card_id,
                'order_id'        => $this->id,
                'user_id'         => null,
                'type'            => \App\Models\LoyaltyPointAdjustment::TYPE_CREDIT,
                'source'          => \App\Models\LoyaltyPointAdjustment::SOURCE_ORDER_CREDIT,
                'points'          => $points,
                'balance_after'   => $balanceAfter,
                'reason'          => "Points gagnés — commande #{$this->id}",
            ]);
        }
    }
}
