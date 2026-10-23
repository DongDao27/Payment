<?php

namespace App\Support;

final class ProductPricing
{
    public static function salePrice(int $listPrice, int $percent): ?int
    {
        return $percent === 0 ? null : intdiv($listPrice * (100 - $percent) + 50, 100);
    }

    public static function percent(int $listPrice, ?int $salePrice): int
    {
        if ($listPrice <= 0 || $salePrice === null || $salePrice >= $listPrice) {
            return 0;
        }

        return (int) round(($listPrice - $salePrice) * 100 / $listPrice);
    }
}
