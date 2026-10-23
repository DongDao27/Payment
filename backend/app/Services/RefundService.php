<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Order;
use App\Models\PaymentReceipt;
use App\Models\Refund;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RefundService
{
    public function record(Order $order, array $data, int $actorId): Refund
    {
        if (config('store.after_sales_policy') === 'no_returns_no_refunds') {
            throw new BusinessException('REFUNDS_NOT_SUPPORTED', 'The store does not accept returns or refunds.', 403);
        }
        if (! config('store.refund_records_enabled')) {
            throw new BusinessException('REFUND_POLICY_NOT_APPROVED', 'Manual refund recording requires an approved policy and operator.', 503);
        }
        $path = 'evidence/'.$data['evidence_reference'];
        if (! Storage::disk('local')->exists($path)) {
            throw new BusinessException('EVIDENCE_REQUIRED', 'Private verified evidence file is missing.', 422);
        }
        try {
            return DB::transaction(function () use ($order, $data, $actorId, $path) {
                $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                $receipt = PaymentReceipt::whereKey($data['receipt_id'])->where('order_id', $order->id)->lockForUpdate()->firstOrFail();
                $amount = (int) $data['amount'];
                if ($amount !== (int) $data['merchandise_amount'] + (int) $data['shipping_amount']
                    || $amount + Refund::where('receipt_id', $receipt->id)->sum('amount') > $receipt->received_amount) {
                    throw new BusinessException('REFUND_AMOUNT_INVALID', 'Refund allocations must match and cannot exceed received money.');
                }
                if ($receipt->allocation === 'accepted'
                    && ((int) $data['merchandise_amount'] + Refund::where('receipt_id', $receipt->id)->sum('merchandise_amount') > $order->items_subtotal - $order->discount_total
                    || (int) $data['shipping_amount'] + Refund::where('receipt_id', $receipt->id)->sum('shipping_amount') > $order->shipping_fee)) {
                    throw new BusinessException('REFUND_ALLOCATION_INVALID', 'Refund cannot exceed collected merchandise or shipping amounts.');
                }

                return Refund::create([
                    'order_id' => $order->id, 'receipt_id' => $receipt->id, 'provider' => $receipt->provider,
                    'external_reference' => $data['external_reference'], 'amount' => $amount,
                    'merchandise_amount' => $data['merchandise_amount'], 'shipping_amount' => $data['shipping_amount'],
                    'refunded_at' => CarbonImmutable::parse($data['refunded_at'])->utc(), 'verified_by' => $actorId,
                    'evidence_path' => $path, 'reason' => $data['reason'],
                ]);
            }, 3);
        } catch (UniqueConstraintViolationException) {
            throw new BusinessException('REFUND_REFERENCE_DUPLICATE', 'External refund reference is already recorded.');
        }
    }
}
