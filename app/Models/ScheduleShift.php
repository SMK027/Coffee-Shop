<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleShift extends Model
{
    public const TYPE_WORK = 'work';
    public const TYPE_LEAVE = 'leave';
    public const TYPE_ABSENCE = 'absence';
    public const TYPE_MEETING = 'meeting';

    public const TYPES = [self::TYPE_WORK, self::TYPE_LEAVE, self::TYPE_ABSENCE, self::TYPE_MEETING];

    public const LABELS = [
        self::TYPE_LEAVE => 'Congé',
        self::TYPE_ABSENCE => 'Absence',
        self::TYPE_MEETING => 'Réunion / formation',
    ];

    protected $fillable = [
        'user_id', 'type', 'date', 'start_time', 'end_time', 'title', 'created_by_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function isFullDay(): bool
    {
        return $this->start_time === null || $this->end_time === null;
    }

    public function typeLabel(): ?string
    {
        return self::LABELS[$this->type] ?? null;
    }

    /** Total en minutes des événements de type "travail" (congés/absences exclus). */
    public static function totalWorkedMinutes(iterable $shifts): int
    {
        $minutes = 0;

        foreach ($shifts as $shift) {
            if ($shift->type !== self::TYPE_WORK || $shift->start_time === null || $shift->end_time === null) {
                continue;
            }

            $start = strtotime((string) $shift->start_time);
            $end = strtotime((string) $shift->end_time);

            if ($start !== false && $end !== false && $end > $start) {
                $minutes += (int) round(($end - $start) / 60);
            }
        }

        return $minutes;
    }

    /** Formate une durée en minutes sous la forme "7h 30min", "8h" ou "45min". */
    public static function formatDuration(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remainder = $minutes % 60;

        if ($hours > 0 && $remainder > 0) {
            return "{$hours}h {$remainder}min";
        }

        if ($hours > 0) {
            return "{$hours}h";
        }

        return "{$remainder}min";
    }
}
