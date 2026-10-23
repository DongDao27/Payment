<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'product_id',
        'storage_disk',
        'path',
        'mime',
        'byte_size',
        'sort_order',
        'alt_text',
    ];

    protected function casts(): array
    {
        return [
            'byte_size' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
