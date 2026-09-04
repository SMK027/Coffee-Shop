<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'drink_id', 'quantity', 'unit_price', 'custom_label', 'custom_price', 'is_refund', 'refund_item_id'];

    protected $casts = [
        'unit_price'      => 'decimal:2',
        'custom_price'    => 'decimal:2',
        'is_refund'       => 'boolean',
        'refund_item_id'  => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function drink(): BelongsTo
    {
        return $this->belongsTo(Drink::class);
    }

    /** Nom affiché : boisson du catalogue ou libellé libre. */
    public function getDisplayNameAttribute(): string
    {
        return $this->drink?->name ?? $this->custom_label ?? 'Article personnalisé';
    }

    public function getSubtotalAttribute(): float
    {
        return $this->quantity * $this->unit_price;
    }

    /**
     * Prix de remboursement partiel : applique proportionnellement les remises
     * globales de la commande (fidélité, salarié, bon d'achat, offre carte) pour
     * ne jamais rembourser plus que le montant réellement payé par le client.
     */
    public function getRefundUnitPriceAttribute(): float
    {
        $rawPrice = abs((float) $this->unit_price);
        $ratio    = $this->order?->refund_discount_ratio ?? 0.0;

        return round($rawPrice * (1 - $ratio), 2);
    }
}
