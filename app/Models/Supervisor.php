<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

#[Fillable(['supervisor_number', 'password', 'is_active', 'superadmin_id'])]
#[Hidden(['password'])]
class Supervisor extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function superadmin(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'superadmin_id');
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
