<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quote extends Model
{
    use HasUuids;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'user_id',
        'cart_id',
        'cart_fingerprint',
        'payload',
        'items_subtotal',
        'discount_total',
        'shipping_fee',
        'payable_total',
        'pricing_version',
        'expires_at',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'items_subtotal' => 'integer',
            'discount_total' => 'integer',
            'shipping_fee' => 'integer',
            'payable_total' => 'integer',
            'expires_at' => 'immutable_datetime',
            'consumed_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }
}
