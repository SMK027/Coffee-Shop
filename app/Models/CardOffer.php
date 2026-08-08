<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardOffer extends Model
{
    protected $fillable = [
        'loyalty_card_id', 'label', 'discount_type', 'discount_value',
        'max_discount_amount', 'expires_at', 'is_used', 'used_at',
        'used_in_order_id', 'issued_by',
    ];

    protected $casts = [
        'discount_value'      => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'expires_at'          => 'datetime',
        'used_at'             => 'datetime',
        'is_used'             => 'boolean',
    ];

    public const TYPE_FIXED   = 'fixed';
    public const TYPE_PERCENT = 'percent';

    public function loyaltyCard(): BelongsTo
    {
        return $this->belongsTo(LoyaltyCard::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function usedInOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'used_in_order_id');
    }

    public function isValid(): bool
    {
        return ! $this->is_used && $this->expires_at->isFuture();
    }

    public function getDisplayValueAttribute(): string
    {
        if ($this->discount_type === self::TYPE_PERCENT) {
            $base = rtrim(rtrim((string) $this->discount_value, '0'), '.') . ' %';
            if ($this->max_discount_amount !== null) {
                return $base . ' (max ' . number_format((float) $this->max_discount_amount, 2, ',', ' ') . ' €)';
            }
            return $base;
        }

        return number_format((float) $this->discount_value, 2, ',', ' ') . ' €';
    }
}
