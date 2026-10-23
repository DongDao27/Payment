<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_code' => $this->order_code, 'fulfillment_method' => $this->fulfillment_method,
            'recipient_name' => $this->recipient_name, 'recipient_phone' => $this->recipient_phone, 'address' => $this->address,
            'lifecycle' => $this->lifecycle, 'payment_status' => $this->payment_status, 'fulfillment_status' => $this->fulfillment_status,
            'expires_at' => $this->expires_at->toISOString(), 'paid_at' => $this->paid_at?->toISOString(),
            'items' => OrderLineResource::collection($this->whenLoaded('items')),
            'totals' => ['items_subtotal' => $this->items_subtotal, 'discount_total' => $this->discount_total, 'shipping_fee' => $this->shipping_fee, 'payable_total' => $this->payable_total],
            'version' => $this->version,
        ];
    }
}
