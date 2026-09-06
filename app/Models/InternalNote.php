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
        'is_for_all', 'background_color', 'text_color', 'font_family', 'expires_at',
    ];

    public const FONT_FAMILIES = [
        'Figtree, ui-sans-serif, system-ui, sans-serif' => 'Police du site',
        'Arial, sans-serif' => 'Arial',
        'Verdana, sans-serif' => 'Verdana',
        'Trebuchet MS, sans-serif' => 'Trebuchet MS',
        'Georgia, serif' => 'Georgia',
        'Times New Roman, serif' => 'Times New Roman',
        'Courier New, monospace' => 'Courier New',
        'cursive' => 'Cursive',
    ];

    protected $casts = [
        'is_for_all' => 'boolean',
        'expires_at' => 'datetime',
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
        if ($this->expires_at?->isPast()) {
            return false;
        }

        if ($this->display_location === 'global_banner') {
            return true;
        }

        if (! $user || ! $user->isActive()) {
            return false;
        }

        if ($this->is_for_all || ($this->target_role && $this->target_role === $user->global_role)) {
            return true;
        }

        return $this->recipients()->whereKey($user->id)->exists();
    }

    public function scopeForUser($query, User $user)
    {
        return $query->active()->where(function ($notes) use ($user) {
            $notes->where('is_for_all', true)
                ->orWhere('target_role', $user->global_role)
                ->orWhereHas('recipients', fn ($recipients) => $recipients->whereKey($user->id));
        });
    }

    public function scopeActive($query)
    {
        return $query->where(function ($notes) {
            $notes->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }
}
