<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\IdempotencyKey;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\PaymentAttempt;
use App\Models\PaymentEvent;
use App\Models\PaymentReceipt;
use App\Models\Product;
use App\Models\ReconciliationCase;
use App\Models\StockReservation;
use App\Payments\PaymentGateway;
use App\Payments\VerifiedPayment;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(private PaymentGateway $gateway, private InventoryService $inventory) {}

    public function create(Order $order, int $actorId, string $key, bool $qr = false): PaymentAttempt
    {
        if (config('payments.vnpay.deferred', true)) {
            throw new BusinessException('PAYMENT_INTEGRATION_DEFERRED', 'VNPAY integration is deferred; new payment requests are not enabled.', 503);
        }
        $this->gateway->assertAvailable($qr);

        return DB::transaction(function () use ($order, $actorId, $key, $qr) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->lifecycle !== 'open' || $order->payment_status === 'paid' || now()->gte($order->expires_at)) {
                throw new BusinessException('ORDER_NOT_PAYABLE', 'The order is paid, closed or expired.');
            }
            // D55: customers pay delivery and pickup online; admin in-store QR stays pickup-only.
            if ($qr && $order->fulfillment_method !== 'pickup') {
                throw new BusinessException('PAYMENT_CHANNEL_INVALID', 'Payment channel does not match order fulfillment.');
            }
            $operation = 'payment:'.$order->id;
            $existing = IdempotencyKey::where('user_id', $actorId)->where('operation', $operation)->where('key', $key)->lockForUpdate()->first();
            if ($existing) {
                return PaymentAttempt::findOrFail($existing->cached_response['attempt_id']);
            }
            $attempt = $order->attempts()->whereIn('status', ['created', 'pending', 'unknown'])->orderByDesc('created_at')->lockForUpdate()->first();
            if (! $attempt && $order->payment_status === 'reconciliation_required') {
                throw new BusinessException('PAYMENT_RECONCILIATION_REQUIRED', 'Resolve uncertain payment before retrying.');
            }
            if (! $attempt) {
                $attempt = PaymentAttempt::create([
                    'order_id' => $order->id, 'provider' => 'vnpay', 'provider_reference' => str_replace('-', '', (string) Str::uuid()),
                    'requested_amount' => $order->payable_total, 'status' => 'pending', 'expires_at' => $order->expires_at,
                ]);
                $order->update(['payment_status' => 'pending', 'version' => $order->version + 1]);
            }
            IdempotencyKey::create(['user_id' => $actorId, 'operation' => $operation, 'key' => $key, 'request_hash' => hash('sha256', $order->order_code), 'status' => 'succeeded', 'order_id' => $order->id, 'cached_response' => ['attempt_id' => $attempt->id]]);

            return $attempt;
        }, 3);
    }

    public function present(PaymentAttempt $attempt, string $ip): array
    {
        return ['id' => $attempt->id, 'order_code' => $attempt->order->order_code, 'status' => $attempt->status, 'expires_at' => $attempt->expires_at->toISOString(), 'payment_url' => in_array($attempt->status, ['created', 'pending'], true) && now()->lt($attempt->expires_at) ? $this->gateway->paymentUrl($attempt, $ip) : null, 'qr_content' => null, 'provider' => $attempt->provider];
    }

    public function status(Order $order): array
    {
        $latest = $order->attempts()->orderByDesc('created_at')->orderByDesc('id')->first();
        $active = $order->attempts()->whereIn('status', ['created', 'pending', 'unknown'])->exists();

        return ['payment_status' => $order->payment_status, 'latest_attempt_status' => $latest?->status, 'expires_at' => $order->expires_at->toISOString(), 'can_retry' => $order->lifecycle === 'open' && $order->payment_status === 'unpaid' && ! $active && now()->lt($order->expires_at), 'reconciliation_required' => $order->payment_status === 'reconciliation_required' || $order->receipts()->whereIn('allocation', ['duplicate', 'late', 'mismatch'])->whereHas('reconciliationCases', fn ($q) => $q->where('status', 'open'))->exists()];
    }

    public function apply(VerifiedPayment $result, string $source): string
    {
        $candidate = PaymentAttempt::where('provider_reference', $result->reference)->first();
        if (! $candidate) {
            return '01';
        }
        try {
            return DB::transaction(function () use ($candidate, $result, $source) {
                $order = Order::whereKey($candidate->order_id)->lockForUpdate()->firstOrFail();
                $itemIds = $order->items()->pluck('product_id');
                Product::whereIn('id', $itemIds)->orderBy('id')->lockForUpdate()->get();
                $attempt = PaymentAttempt::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
                $fingerprint = hash('sha256', $source.':'.$result->fingerprint);
                $priorEvent = PaymentEvent::where('fingerprint', $fingerprint)->first();
                if ($priorEvent) {
                    return $priorEvent->disposition === 'mismatch' ? '04' : '02';
                }
                $event = PaymentEvent::create(['source' => $source, 'provider_reference' => $result->reference, 'fingerprint' => $fingerprint, 'signature_valid' => true, 'safe_payload' => $result->safePayload, 'disposition' => 'processing', 'created_at' => now()]);
                if ($result->state !== 'succeeded') {
                    if ($attempt->status !== 'succeeded' && $order->payment_status !== 'paid') {
                        $state = $result->state === 'failed' ? 'failed' : 'unknown';
                        $attempt->update(['status' => $state, 'verified_at' => now()]);
                        $active = $order->attempts()->whereIn('status', ['created', 'pending', 'unknown'])->exists();
                        if ($order->payment_status !== 'reconciliation_required') {
                            $order->update(['payment_status' => $active ? 'pending' : 'unpaid', 'version' => $order->version + 1]);
                        }
                    }
                    $event->update(['disposition' => $result->state, 'processed_at' => now()]);

                    return '00';
                }
                if (! $result->transactionId || ! $result->paidAt || $result->paidAt->gt(now()->addMinutes(5)) || $result->paidAt->lt($attempt->created_at->copy()->startOfSecond())) {
                    $this->openCase($order, null, 'untrusted_payment_time');
                    $attempt->update(['status' => 'unknown']);
                    $event->update(['disposition' => 'reconciliation_required', 'processed_at' => now()]);

                    return '00';
                }
                $existing = PaymentReceipt::where('provider', 'vnpay')->where('provider_transaction_id', $result->transactionId)->lockForUpdate()->first();
                if ($existing) {
                    $event->update(['receipt_id' => $existing->id, 'disposition' => 'duplicate_event', 'processed_at' => now()]);

                    return '02';
                }
                $allocation = 'accepted';
                if ($result->amount !== $attempt->requested_amount || $result->amount !== $order->payable_total) {
                    $allocation = 'mismatch';
                } elseif ($order->payment_status === 'paid') {
                    $allocation = 'duplicate';
                } elseif ($order->lifecycle !== 'open' || $result->paidAt->gte($order->expires_at)
                    || StockReservation::whereIn('order_item_id', $order->items()->pluck('id'))->where('status', '!=', 'active')->exists()
                    || StockReservation::whereIn('order_item_id', $order->items()->pluck('id'))->count() !== $order->items()->count()) {
                    $allocation = 'late';
                }
                $receipt = PaymentReceipt::create(['provider' => 'vnpay', 'provider_transaction_id' => $result->transactionId, 'order_id' => $order->id, 'attempt_id' => $attempt->id, 'received_amount' => $result->amount, 'provider_paid_at' => $result->paidAt, 'verified_at' => now(), 'allocation' => $allocation]);
                $attempt->update(['status' => 'succeeded', 'provider_transaction_id' => $result->transactionId, 'verified_at' => now()]);
                if ($allocation === 'accepted') {
                    $this->inventory->consume($order);
                    $order->update(['payment_status' => 'paid', 'paid_at' => $result->paidAt, 'version' => $order->version + 1]);
                    OrderStatusHistory::create(['order_id' => $order->id, 'event' => 'payment_verified', 'before' => ['payment_status' => 'pending'], 'after' => ['payment_status' => 'paid']]);
                } else {
                    $this->openCase($order, $receipt, $allocation);
                }
                $event->update(['receipt_id' => $receipt->id, 'disposition' => $allocation, 'processed_at' => now()]);

                return $allocation === 'mismatch' ? '04' : '00';
            }, 3);
        } catch (UniqueConstraintViolationException) {
            // Another order/worker already stored this provider transaction; no second allocation.
            return '02';
        }
    }

    public function openCase(Order $order, ?PaymentReceipt $receipt, string $reason): void
    {
        ReconciliationCase::firstOrCreate(['order_id' => $order->id, 'receipt_id' => $receipt?->id, 'reason' => $reason, 'status' => 'open']);
        if ($order->payment_status !== 'paid') {
            $order->update(['payment_status' => 'reconciliation_required', 'version' => $order->version + 1]);
        }
    }
}
