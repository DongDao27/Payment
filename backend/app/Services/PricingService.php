<?php

namespace App\Services;

use App\Models\Product;

class PricingService
{
    public function unitPrice(Product $product): int
    {
        return $product->sale_price ?? $product->list_price;
    }
}
