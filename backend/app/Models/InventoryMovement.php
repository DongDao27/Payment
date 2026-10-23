<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'product_id',
        'order_id',
        'actor_id',
        'kind',
        'delta_on_hand',
        'delta_reserved',
        'resulting_on_hand',
        'resulting_reserved',
        'reason',
        'business_key',
    ];

    protected function casts(): array
    {
        return [
            'delta_on_hand' => 'integer',
            'delta_reserved' => 'integer',
            'resulting_on_hand' => 'integer',
            'resulting_reserved' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
