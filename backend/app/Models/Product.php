<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'category_id',
        'sku',
        'slug',
        'name',
        'description',
        'grade',
        'list_price',
        'sale_price',
        'stock_on_hand',
        'stock_reserved',
        'is_active',
        'is_hot',
        'is_bestseller',
        'catalog_version',
    ];

    protected function casts(): array
    {
        return [
            'grade' => 'integer',
            'list_price' => 'integer',
            'sale_price' => 'integer',
            'stock_on_hand' => 'integer',
            'stock_reserved' => 'integer',
            'is_active' => 'boolean',
            'is_hot' => 'boolean',
            'is_bestseller' => 'boolean',
            'catalog_version' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }
}
