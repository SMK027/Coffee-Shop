<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

#[Fillable(['username', 'name', 'email', 'password', 'global_role', 'avatar', 'bio', 'is_active', 'superadmin_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'role' => $this->global_role,
            'name' => $this->name,
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

    public function isSupervisor(): bool
    {
        return $this->global_role === 'supervisor';
    }

    public function isModerator(): bool
    {
        return $this->global_role === 'moderator';
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function createdBySuperAdmin()
    {
        return $this->belongsTo(self::class, 'superadmin_id');
    }

    public function loyaltyCards(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoyaltyCard::class);
    }

    /** Jeton signé de connexion par QR code, invalidé automatiquement si le mot de passe change. */
    public function loginToken(): string
    {
        $payload = $this->username . '|' . $this->password;
        $signature = substr(hash_hmac('sha256', $payload, (string) config('app.key')), 0, 20);

        return $this->username . '.' . $signature;
    }

    public function loginBarcodeValue(): string
    {
        return 'USERLOGIN:' . $this->loginToken();
    }

    /** Résout et vérifie un jeton de connexion par QR code (retourne null si invalide). */
    public static function fromLoginToken(string $token): ?self
    {
        $tokenCompact = preg_replace('/\s+/', '', trim($token)) ?? trim($token);
        $tokenCompact = preg_replace('/^USERLOGIN:/', '', $tokenCompact) ?? $tokenCompact;

        if (preg_match('/^([A-Za-z0-9_-]{1,50})\.([A-Fa-f0-9]{20})$/', $tokenCompact, $matches) !== 1) {
            return null;
        }

        $user = static::where('username', $matches[1])->first();
        if (! $user) {
            return null;
        }

        $payload = $user->username . '|' . $user->password;
        $expected = substr(hash_hmac('sha256', $payload, (string) config('app.key')), 0, 20);

        return hash_equals($expected, strtolower($matches[2])) ? $user : null;
    }
}
