<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalNoteAttachment extends Model
{
    protected $fillable = ['internal_note_id', 'original_name', 'path', 'mime_type', 'size'];

    public function note(): BelongsTo
    {
        return $this->belongsTo(InternalNote::class, 'internal_note_id');
    }
}
