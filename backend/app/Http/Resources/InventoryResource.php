<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['product_id' => $this->id, 'sku' => $this->sku, 'name' => $this->name, 'stock_on_hand' => $this->stock_on_hand, 'stock_reserved' => $this->stock_reserved, 'available_quantity' => $this->stock_on_hand - $this->stock_reserved];
    }
}
