<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyReport extends Model
{
    protected $fillable = [
        'report_date', 'generated_by', 'total_collected', 'total_refunded',
        'breakdown', 'refund_breakdown',
    ];

    protected $casts = [
        'report_date'     => 'date',
        'total_collected' => 'decimal:2',
        'total_refunded'  => 'decimal:2',
        'breakdown'       => 'array',
        'refund_breakdown' => 'array',
    ];

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
