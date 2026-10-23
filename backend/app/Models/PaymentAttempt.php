<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAttempt extends Model
{
    use HasUuids;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'order_id',
        'provider',
        'provider_reference',
        'requested_amount',
        'status',
        'expires_at',
        'provider_transaction_id',
        'query_requested_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_amount' => 'integer',
            'expires_at' => 'immutable_datetime',
            'query_requested_at' => 'immutable_datetime',
            'verified_at' => 'immutable_datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
