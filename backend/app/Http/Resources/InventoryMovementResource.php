<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'kind' => $this->kind,
            'delta_on_hand' => $this->delta_on_hand, 'delta_reserved' => $this->delta_reserved,
            'resulting_on_hand' => $this->resulting_on_hand, 'resulting_reserved' => $this->resulting_reserved,
            'reason' => $this->reason, 'actor_name' => $this->actor?->name,
            'order_code' => $this->order?->order_code, 'created_at' => $this->created_at->toISOString(),
        ];
    }
}
