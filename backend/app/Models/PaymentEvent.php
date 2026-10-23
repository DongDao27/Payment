<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PaymentEvent extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'source',
        'provider_reference',
        'fingerprint',
        'signature_valid',
        'safe_payload',
        'disposition',
        'receipt_id',
        'processed_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'safe_payload' => 'array',
            'signature_valid' => 'boolean',
            'processed_at' => 'immutable_datetime',
        ];
    }
}
