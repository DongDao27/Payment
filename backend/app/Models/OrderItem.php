<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'order_id',
        'product_id',
        'sku',
        'name',
        'list_unit_price',
        'charged_unit_price',
        'quantity',
        'discount_line_total',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'list_unit_price' => 'integer',
            'charged_unit_price' => 'integer',
            'quantity' => 'integer',
            'discount_line_total' => 'integer',
            'line_total' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reservation(): HasOne
    {
        return $this->hasOne(StockReservation::class);
    }
}
