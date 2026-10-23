<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReconciliationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'order_code' => $this->order->order_code, 'reason' => $this->reason, 'status' => $this->status, 'note' => $this->note, 'version' => $this->version];
    }
}
