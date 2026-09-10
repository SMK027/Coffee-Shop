<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupervisorSecurityKey extends Model
{
    protected $fillable = [
        'supervisor_id', 'name', 'credential_id', 'credential', 'registered_ip',
    ];

    protected $casts = [
        'credential' => 'json',
        'last_used_at' => 'datetime',
    ];

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Supervisor::class);
    }
}
