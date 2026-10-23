<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'order_code',
        'user_id',
        'quote_id',
        'fulfillment_method',
        'recipient_name',
        'recipient_phone',
        'address',
        'items_subtotal',
        'discount_total',
        'shipping_fee',
        'payable_total',
        'lifecycle',
        'payment_status',
        'fulfillment_status',
        'expires_at',
        'paid_at',
        'completed_at',
        'cancelled_at',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'address' => 'array',
            'items_subtotal' => 'integer',
            'discount_total' => 'integer',
            'shipping_fee' => 'integer',
            'payable_total' => 'integer',
            'expires_at' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'version' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class, 'order_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(PaymentReceipt::class, 'order_id');
    }

    public function getRouteKeyName(): string
    {
        return 'order_code';
    }
}
