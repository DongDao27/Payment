<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use HasUuids;

    protected $hidden = ['evidence_path'];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'order_id',
        'receipt_id',
        'provider',
        'external_reference',
        'amount',
        'merchandise_amount',
        'shipping_amount',
        'refunded_at',
        'verified_by',
        'evidence_path',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'merchandise_amount' => 'integer',
            'shipping_amount' => 'integer',
            'refunded_at' => 'immutable_datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(PaymentReceipt::class);
    }
}
