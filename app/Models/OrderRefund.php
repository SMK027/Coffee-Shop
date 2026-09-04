<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderRefund extends Model
{
    protected $fillable = ['order_id', 'payment_method_id', 'amount', 'reason', 'created_by', 'type'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    const TYPE_PARTIAL = 'partial';
    const TYPE_TOTAL = 'total';
    const TYPE_CUSTOM = 'custom';

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
