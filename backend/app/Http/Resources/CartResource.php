<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $warnings = [];
        $items = $this->items->map(function ($item) use (&$warnings) {
            $product = $item->product;
            $available = $product->is_active && $product->category->is_active && $product->stock_on_hand - $product->stock_reserved >= $item->quantity;
            if (! $available) {
                $warnings[] = 'Product '.$product->id.' is unavailable at the requested quantity.';
            }

            return ['id' => $item->id, 'product' => new ProductResource($product), 'quantity' => $item->quantity, 'line_total' => ($product->sale_price ?? $product->list_price) * $item->quantity, 'is_available' => $available];
        });

        return ['id' => $this->id, 'items' => $items, 'estimated_total' => $items->sum('line_total'), 'warnings' => $warnings];
    }
}
