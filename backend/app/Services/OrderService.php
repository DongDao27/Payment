<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(private ReconciliationService $reconciliation) {}

    public function cancel(Order $order, int $actorId): Order
    {
        if ($order->payment_status === 'paid' || $order->lifecycle === 'completed') {
            throw new BusinessException('ORDER_NOT_CANCELLABLE', 'The order cannot be cancelled.');
        }
        $order = $this->reconciliation->close($order, true, $actorId);
        if ($order->lifecycle === 'open') {
            // Conflict is raised after uncertain evidence commits, preserving the case.
            throw new BusinessException('PAYMENT_RECONCILIATION_REQUIRED', 'Payment verification is required before cancelling.');
        }

        return $order;
    }

    public function fulfill(Order $order, string $target, int $version, int $actorId): Order
    {
        return DB::transaction(function () use ($order, $target, $version, $actorId) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->version !== $version) {
                throw new BusinessException('VERSION_CONFLICT', 'Order changed. Reload before updating.');
            }
            $next = $order->fulfillment_method === 'pickup'
                ? ['unprocessed' => 'preparing', 'preparing' => 'ready_for_pickup', 'ready_for_pickup' => 'fulfilled']
                : ['unprocessed' => 'preparing', 'preparing' => 'shipping', 'shipping' => 'fulfilled'];
            if ($order->lifecycle !== 'open' || ($next[$order->fulfillment_status] ?? null) !== $target) {
                throw new BusinessException('INVALID_FULFILLMENT_TRANSITION', 'Fulfillment transition is not allowed.');
            }
            if (($order->fulfillment_method === 'delivery' || $target === 'fulfilled') && $order->payment_status !== 'paid') {
                throw new BusinessException('PAYMENT_REQUIRED', 'Verified payment is required for this transition.');
            }
            if ($order->payment_status !== 'paid' && now()->gte($order->expires_at)) {
                throw new BusinessException('ORDER_EXPIRED', 'Unpaid order is past its hold deadline.');
            }
            $before = $order->fulfillment_status;
            $order->update(['fulfillment_status' => $target, 'version' => $version + 1, 'lifecycle' => $target === 'fulfilled' ? 'completed' : 'open', 'completed_at' => $target === 'fulfilled' ? now() : null]);
            OrderStatusHistory::create(['order_id' => $order->id, 'actor_id' => $actorId, 'event' => 'fulfillment', 'before' => ['fulfillment_status' => $before], 'after' => ['fulfillment_status' => $target], 'request_id' => request()->attributes->get('request_id')]);

            return $order->load('items');
        }, 3);
    }
}
