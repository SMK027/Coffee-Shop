<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LoyaltyCard extends Model
{
    protected $fillable = [
        'card_number', 'last_name', 'first_name', 'email', 'phone', 'birth_date', 'pin', 'points', 'user_id',
    ];

    protected $hidden = ['pin'];

    protected $casts = [
        'birth_date' => 'date',
        'pin'        => 'hashed',
        'points'     => 'integer',
    ];

    /** Âge minimum requis pour détenir une carte de fidélité. */
    public const MIN_AGE = 15;

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function pinResets(): HasMany
    {
        return $this->hasMany(LoyaltyPinReset::class);
    }

    /**
     * Ajustements manuels de points (débit/crédit) réalisés par les super admins.
     */
    public function pointAdjustments(): HasMany
    {
        return $this->hasMany(LoyaltyPointAdjustment::class);
    }

    /**
     * Compte employé (salarié) auquel la carte est éventuellement rattachée.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * La carte bénéficie des avantages salariés tant qu'elle est rattachée
     * à un compte employé existant. Le lien est rompu automatiquement à la
     * suppression du compte (clé étrangère nullOnDelete).
     */
    public function hasEmployeeBenefits(): bool
    {
        return $this->user_id !== null;
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getAgeAttribute(): int
    {
        return $this->birth_date->age;
    }

    /**
     * Génère un numéro de carte unique de 12 chiffres.
     */
    public static function generateCardNumber(): string
    {
        do {
            $number = (string) random_int(100000000000, 999999999999);
        } while (static::where('card_number', $number)->exists());

        return $number;
    }

    /**
     * Vérifie qu'une date de naissance respecte l'âge minimum requis.
     */
    public static function meetsMinimumAge(string|Carbon $birthDate): bool
    {
        $date = $birthDate instanceof Carbon ? $birthDate : Carbon::parse($birthDate);

        return $date->age >= self::MIN_AGE;
    }
}
