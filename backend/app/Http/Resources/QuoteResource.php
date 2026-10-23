<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'expires_at' => $this->expires_at->toISOString(),
            'cart_fingerprint' => $this->cart_fingerprint, 'pricing_version' => (int) $this->pricing_version,
            'items' => OrderLineResource::collection($this->payload['items']), 'totals' => $this->payload['totals'],
        ];
    }
}
