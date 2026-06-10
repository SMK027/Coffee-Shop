<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    protected $fillable = [
        'name', 'email', 'subject', 'message', 'status', 'reply', 'handled_by', 'replied_at',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
    ];

    const STATUS_LABELS = [
        'new'      => 'Nouveau',
        'read'     => 'Lu',
        'replied'  => 'Répondu',
        'archived' => 'Archivé',
    ];

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
