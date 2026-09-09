<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

#[Fillable(['supervisor_number', 'password', 'is_active', 'quarantined_until', 'is_temporary', 'is_manual_temporary', 'replaces_supervisor_id', 'temporary_expires_at', 'superadmin_id', 'holder_admin_id', 'permissions'])]
#[Hidden(['password'])]
class Supervisor extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_temporary' => 'boolean',
            'is_manual_temporary' => 'boolean',
            'quarantined_until' => 'datetime',
            'temporary_expires_at' => 'datetime',
            'permissions' => 'array',
        ];
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active
            && ! ($this->quarantined_until?->isFuture())
            && ! ($this->is_temporary && $this->temporary_expires_at?->isPast());
    }

    /**
     * Indique si ce superviseur est habilité à effectuer l'opération donnée
     * (voir App\Support\SupervisorOperation). Un superviseur possède de 0 à n
     * habilitations ; une opération non couverte par le catalogue reste
     * autorisée (aucune restriction définie).
     */
    public function hasHabilitation(string $operation): bool
    {
        return in_array($operation, $this->permissions ?? [], true);
    }

    public function superadmin(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'superadmin_id');
    }

    public function holderAdmin(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'holder_admin_id');
    }

    public function replacedSupervisor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_supervisor_id');
    }

    public function bypassToken(): string
    {
        $payload = $this->supervisor_number . '|' . $this->password;
        $signature = substr(hash_hmac('sha256', $payload, (string) config('app.key')), 0, 20);

        return $this->supervisor_number . '.' . $signature;
    }

    public function barcodeValue(): string
    {
        return 'SUPERVISOR:' . $this->bypassToken();
    }
}
