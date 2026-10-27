<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\PaymentAttempt;
use App\Models\PaymentEvent;
use App\Models\ReconciliationCase;
use App\Models\Refund;
use App\Payments\PaymentGateway;
use App\Payments\VerifiedPayment;
use Illuminate\Support\Facades\DB;

class ReconciliationService
{
    public function __construct(private PaymentGateway $gateway, private PaymentService $payments, private InventoryService $inventory) {}

    public function refresh(Order $order): Order
    {
        foreach ($order->attempts()->whereIn('status', ['created', 'pending', 'unknown'])->orderBy('created_at')->limit(3)->get() as $candidate) {
            $attempt = $this->claimQuery($candidate);
            if (! $attempt) {
                continue;
            }
            try {
                // All network I/O runs after the short database transaction has committed.
                $verified = $this->gateway->query($attempt);
            } catch (BusinessException) {
                $verified = null;
            }
            if ($verified) {
                $this->payments->apply($verified, 'query');
            } else {
                DB::transaction(function () use ($order, $attempt) {
                    $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                    $attempt = PaymentAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
                    if ($locked->payment_status !== 'paid' && $attempt->status !== 'succeeded') {
                        $attempt->update(['status' => 'unknown']);
                        $this->payments->openCase($locked, null, 'provider_query_uncertain');
                    }
                }, 3);
            }
        }

        return $order->fresh();
    }

    public function confirmReturn(VerifiedPayment $returned): ?Order
    {
        $candidate = PaymentAttempt::where('provider_reference', $returned->reference)->first();
        if (! $candidate) {
            return null;
        }
        // A signed browser return never marks paid. A signed failure frees the order for retry;
        // anything else only triggers a provider query, and uncertainty stays pending for IPN/scheduler.
        if ($returned->state === 'failed') {
            $this->payments->apply($returned, 'return');

            return $candidate->order()->firstOrFail();
        }
        $attempt = $this->claimQuery($candidate);
        if ($attempt) {
            try {
                $verified = $this->gateway->query($attempt);
            } catch (BusinessException) {
                $verified = null;
            }
            if ($verified) {
                $this->payments->apply($verified, 'query');
            }
        }

        return $candidate->order()->firstOrFail();
    }

    private function claimQuery(PaymentAttempt $candidate): ?PaymentAttempt
    {
        return DB::transaction(function () use ($candidate) {
            $locked = Order::whereKey($candidate->order_id)->lockForUpdate()->firstOrFail();
            $attempt = PaymentAttempt::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
            if ($locked->payment_status === 'paid' || ! in_array($attempt->status, ['created', 'pending', 'unknown'], true)
                || ($attempt->query_requested_at && $attempt->query_requested_at->gt(now()->subMinutes(5)))) {
                return null;
            }
            $attempt->update(['query_requested_at' => now()]);

            return $attempt;
        }, 3);
    }

    public function close(Order $order, bool $cancel = false, ?int $actorId = null): Order
    {
        if ($order->lifecycle !== 'open' || $order->payment_status === 'paid') {
            return $order->load('items');
        }
        $this->refresh($order);

        return DB::transaction(function () use ($order, $cancel, $actorId) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->lifecycle !== 'open' || $order->payment_status === 'paid') {
                return $order->load('items');
            }
            if (! $cancel && now()->lt($order->expires_at)) {
                return $order->load('items');
            }
            $active = $order->attempts()->whereIn('status', ['created', 'pending', 'unknown', 'succeeded'])->exists();
            if ($active || $order->payment_status !== 'unpaid' || ReconciliationCase::where('order_id', $order->id)->where('status', 'open')->exists()) {
                $this->payments->openCase($order, null, 'close_requires_verification');

                return $order->load('items');
            }
            $this->inventory->release($order);
            $target = $cancel && now()->lt($order->expires_at) ? 'cancelled' : 'expired';
            $order->update(['lifecycle' => $target, 'cancelled_at' => $target === 'cancelled' ? now() : null, 'version' => $order->version + 1]);
            OrderStatusHistory::create(['order_id' => $order->id, 'actor_id' => $actorId, 'event' => $target, 'before' => ['lifecycle' => 'open'], 'after' => ['lifecycle' => $target], 'reason' => $cancel ? 'Customer cancellation' : 'Reservation deadline reached', 'request_id' => request()->attributes->get('request_id')]);

            return $order->load('items');
        }, 3);
    }

    public function resolve(ReconciliationCase $case, array $data, int $actorId): ReconciliationCase
    {
        return DB::transaction(function () use ($case, $data, $actorId) {
            $order = Order::whereKey($case->order_id)->lockForUpdate()->firstOrFail();
            $case = ReconciliationCase::whereKey($case->id)->lockForUpdate()->firstOrFail();
            if ($case->version !== (int) $data['version'] || $case->status !== 'open') {
                throw new BusinessException('VERSION_CONFLICT', 'Reconciliation case changed or is already resolved.');
            }
            if ($data['resolution'] === 'manual_review') {
                $case->update(['note' => $data['note'], 'version' => $case->version + 1]);

                return $case;
            }
            if (blank($data['evidence_reference'] ?? null)) {
                throw new BusinessException('EVIDENCE_REQUIRED', 'Verified evidence is required to close this case.', 422);
            }
            if ($data['resolution'] === 'verified_unpaid') {
                if ($order->receipts()->exists() || $order->payment_status === 'paid') {
                    throw new BusinessException('PAYMENT_EVIDENCE_CONFLICT', 'Recorded money cannot be declared unpaid.');
                }
                foreach ($order->attempts as $attempt) {
                    if (! in_array($attempt->status, ['failed', 'cancelled', 'expired'], true)
                        || ! PaymentEvent::where('source', 'query')->where('provider_reference', $attempt->provider_reference)->where('signature_valid', true)->where('disposition', 'failed')->exists()) {
                        throw new BusinessException('PAYMENT_NOT_VERIFIED_UNPAID', 'A signed final provider query is required for every attempt.');
                    }
                }
            } else {
                $receipt = $case->receipt;
                if (! $receipt || Refund::where('receipt_id', $receipt->id)->sum('amount') < $receipt->received_amount) {
                    throw new BusinessException('REFUND_EVIDENCE_REQUIRED', 'Verified refund records must cover the unallocated receipt.');
                }
            }
            $case->update(['status' => 'resolved', 'resolution' => $data['resolution'], 'note' => $data['note'].' Evidence: '.$data['evidence_reference'], 'resolved_by' => $actorId, 'resolved_at' => now(), 'version' => $case->version + 1]);
            if ($data['resolution'] === 'verified_unpaid' && ! ReconciliationCase::where('order_id', $order->id)->where('status', 'open')->exists()) {
                $order->update(['payment_status' => 'unpaid', 'version' => $order->version + 1]);
            }

            return $case;
        }, 3);
    }
}
