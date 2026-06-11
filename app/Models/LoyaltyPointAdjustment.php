<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyPointAdjustment extends Model
{
    const TYPE_CREDIT = 'credit';
    const TYPE_DEBIT  = 'debit';

    const SOURCE_MANUAL       = 'manual';
    const SOURCE_ORDER_DEBIT  = 'order_debit';
    const SOURCE_ORDER_CREDIT = 'order_credit';

    protected $fillable = [
        'loyalty_card_id', 'order_id', 'user_id', 'type', 'source', 'points', 'balance_after', 'reason',
    ];

    protected $casts = [
        'points'        => 'integer',
        'balance_after' => 'integer',
    ];

    public function loyaltyCard(): BelongsTo
    {
        return $this->belongsTo(LoyaltyCard::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCredit(): bool
    {
        return $this->type === self::TYPE_CREDIT;
    }

    public function isManual(): bool
    {
        return $this->source === self::SOURCE_MANUAL;
    }
}
