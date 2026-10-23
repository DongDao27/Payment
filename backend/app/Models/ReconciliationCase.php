<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationCase extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'order_id',
        'receipt_id',
        'reason',
        'status',
        'resolution',
        'resolved_by',
        'resolved_at',
        'note',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'immutable_datetime',
            'version' => 'integer',
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
