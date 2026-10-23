<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $line = is_array($this->resource) ? $this->resource : $this->resource->toArray();

        return array_intersect_key($line, array_flip(['product_id', 'sku', 'name', 'list_unit_price', 'charged_unit_price', 'quantity', 'line_total']));
    }
}
