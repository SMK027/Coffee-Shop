<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternalNote extends Model
{
    protected $fillable = [
        'author_id', 'title', 'description', 'display_location', 'target_role',
        'background_color', 'text_color', 'font_family',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'internal_note_recipients');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(InternalNoteAttachment::class);
    }

    public function isVisibleTo(?User $user): bool
    {
        if ($this->display_location === 'global_banner') {
            return true;
        }

        if (! $user || ! $user->isActive()) {
            return false;
        }

        if ($this->target_role && $this->target_role === $user->global_role) {
            return true;
        }

        return $this->recipients()->whereKey($user->id)->exists();
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($notes) use ($user) {
            $notes->where('target_role', $user->global_role)
                ->orWhereHas('recipients', fn ($recipients) => $recipients->whereKey($user->id));
        });
    }
}
