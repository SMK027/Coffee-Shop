<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyPinReset extends Model
{
    public $timestamps = false;

    protected $fillable = ['loyalty_card_id', 'token', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public function loyaltyCard(): BelongsTo
    {
        return $this->belongsTo(LoyaltyCard::class);
    }

    /** Vérifie si le token est encore valide (non utilisé + moins de 30 minutes). */
    public function isValid(): bool
    {
        return is_null($this->used_at)
            && $this->created_at->diffInMinutes(now()) < 30;
    }

    /** Marque le token comme utilisé. */
    public function markAsUsed(): void
    {
        $this->used_at = now();
        $this->save();
    }
}
