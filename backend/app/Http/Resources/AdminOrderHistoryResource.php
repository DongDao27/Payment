<?php

namespace App\Http\Resources;

use App\Models\PaymentAttempt;
use App\Models\PaymentReceipt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminOrderHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $entry = ['id' => (string) $this->id, 'created_at' => $this->created_at->toISOString()];
        if ($this->resource instanceof PaymentAttempt) {
            return $entry + [
                'type' => 'attempt', 'status' => $this->status, 'provider' => $this->provider,
                'amount' => $this->requested_amount, 'verified_at' => $this->verified_at?->toISOString(),
                'expires_at' => $this->expires_at->toISOString(),
            ];
        }
        if ($this->resource instanceof PaymentReceipt) {
            return $entry + [
                'type' => 'receipt', 'status' => $this->allocation, 'provider' => $this->provider,
                'amount' => $this->received_amount, 'verified_at' => $this->verified_at?->toISOString(),
                'transaction_id' => $this->provider_transaction_id, 'paid_at' => $this->provider_paid_at?->toISOString(),
            ];
        }

        return $entry + [
            'type' => 'status', 'event' => $this->event, 'reason' => $this->reason,
            'before' => (object) array_intersect_key($this->before ?? [], array_flip(['lifecycle', 'payment_status', 'fulfillment_status'])),
            'after' => (object) array_intersect_key($this->after ?? [], array_flip(['lifecycle', 'payment_status', 'fulfillment_status'])),
        ];
    }
}
