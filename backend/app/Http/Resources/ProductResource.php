<?php

namespace App\Http\Resources;

use App\Support\ProductPricing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'sku' => $this->sku, 'slug' => $this->slug, 'name' => $this->name,
            'description' => $this->description, 'category' => new CategoryResource($this->whenLoaded('category')),
            'grade' => $this->grade, 'list_price' => $this->list_price, 'sale_price' => $this->sale_price,
            'unit_price' => $this->sale_price ?? $this->list_price,
            'discount_percent' => ProductPricing::percent($this->list_price, $this->sale_price),
            'available_quantity' => $this->stock_on_hand - $this->stock_reserved,
            'is_active' => $this->is_active, 'catalog_version' => $this->catalog_version,
            'sold_count' => $this->whenHas('sold_count', fn () => (int) $this->sold_count),
            'is_hot' => $this->is_hot, 'is_bestseller' => $this->is_bestseller,
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
        ];
    }
}
