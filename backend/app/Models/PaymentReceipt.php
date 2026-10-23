<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentReceipt extends Model
{
    protected $hidden = ['accepted_order_id'];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'provider',
        'provider_transaction_id',
        'order_id',
        'attempt_id',
        'received_amount',
        'provider_paid_at',
        'verified_at',
        'allocation',
    ];

    protected function casts(): array
    {
        return [
            'received_amount' => 'integer',
            'provider_paid_at' => 'immutable_datetime',
            'verified_at' => 'immutable_datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(PaymentAttempt::class);
    }

    public function reconciliationCases(): HasMany
    {
        return $this->hasMany(ReconciliationCase::class, 'receipt_id');
    }
}
