<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Voucher extends Model
{
    protected $fillable = [
        'code', 'amount', 'issued_by', 'issued_by_name',
        'issued_at', 'expires_at', 'is_used', 'used_at',
        'restricted_card_id', 'restricted_name',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'issued_at' => 'datetime',
        'expires_at'=> 'datetime',
        'used_at'   => 'datetime',
        'is_used'   => 'boolean',
    ];

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function restrictedCard(): BelongsTo
    {
        return $this->belongsTo(LoyaltyCard::class, 'restricted_card_id');
    }

    public function usedInOrder(): HasOne
    {
        return $this->hasOne(Order::class, 'voucher_id');
    }

    /** Indique si le bon est toujours utilisable (non expiré et non consommé). */
    public function isValid(): bool
    {
        return ! $this->is_used && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Vérifie que le bon peut être utilisé par ce client.
     * Si une restriction est définie, la commande doit correspondre.
     */
    public function isValidFor(?int $loyaltyCardId, ?string $customerName): bool
    {
        if ($this->restricted_card_id !== null) {
            return $loyaltyCardId !== null && $loyaltyCardId === (int) $this->restricted_card_id;
        }

        if ($this->restricted_name !== null) {
            if (empty($customerName)) {
                return false;
            }

            // Découpe les deux noms en mots, normalise la casse et les trie.
            // Permet de faire correspondre "Jean Dupont" et "Dupont Jean".
            $normalize = static function (string $s): array {
                $s = Str::ascii(mb_strtolower(trim($s)));
                $words = preg_split('/[\s\-]+/u', $s);
                $words = array_values(array_filter($words, fn($w) => $w !== ''));
                sort($words);
                return $words;
            };

            return $normalize($customerName) === $normalize($this->restricted_name);
        }

        return true; // Aucune restriction
    }

    public function scopeValid($query)
    {
        return $query->where('is_used', false)->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())->where('is_used', false);
    }

    /**
     * Génère un code unique de la forme XXXX-XXXX-XXXX
     * (sans les caractères ambigus 0, 1, I, O).
     */
    public static function generateCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $len   = strlen($chars);

        do {
            $raw  = '';
            for ($i = 0; $i < 12; $i++) {
                $raw .= $chars[random_int(0, $len - 1)];
            }
            $code = substr($raw, 0, 4) . '-' . substr($raw, 4, 4) . '-' . substr($raw, 8, 4);
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
