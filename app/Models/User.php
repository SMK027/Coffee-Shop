<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['username', 'name', 'email', 'password', 'global_role', 'avatar', 'bio'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return in_array($this->global_role, ['admin', 'superadmin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->global_role === 'superadmin';
    }

    /**
     * Cartes de fidélité rattachées à ce compte employé (avantages salariés).
     */
    public function loyaltyCards(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoyaltyCard::class);
    }
}
