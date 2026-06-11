<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class LoyaltyDiscount extends Model
{
    protected $fillable = [
        'name',
        'description',
        'points_cost',
        'discount_type',
        'discount_value',
        'is_active',
        'is_sold_out',
        'employee_only',
        'is_permanent',
        'starts_at',
        'ends_at',
        'quantity_limit',
        'quantity_used',
    ];

    protected $casts = [
        'points_cost' => 'integer',
        'discount_value' => 'decimal:2',
        'is_active' => 'boolean',
        'is_sold_out' => 'boolean',
        'employee_only' => 'boolean',
        'is_permanent' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'quantity_limit' => 'integer',
        'quantity_used' => 'integer',
    ];

    public const TYPE_FIXED = 'fixed';
    public const TYPE_PERCENT = 'percent';

    public function isSoldOut(): bool
    {
        return $this->is_sold_out || ($this->quantity_limit !== null && $this->quantity_used >= $this->quantity_limit);
    }

    public function isWithinSchedule(?Carbon $now = null): bool
    {
        if ($this->is_permanent) {
            return true;
        }

        $now = $now ?: now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    public function isValidForUse(?Carbon $now = null): bool
    {
        return $this->is_active && !$this->isSoldOut() && $this->isWithinSchedule($now);
    }

    public function getRemainingQuantityAttribute(): ?int
    {
        if ($this->quantity_limit === null) {
            return null;
        }

        return max(0, $this->quantity_limit - $this->quantity_used);
    }

    public function getDisplayValueAttribute(): string
    {
        if ($this->discount_type === self::TYPE_PERCENT) {
            return rtrim(rtrim((string) $this->discount_value, '0'), '.') . ' %';
        }

        return number_format((float) $this->discount_value, 2, ',', ' ') . ' EUR';
    }
}
