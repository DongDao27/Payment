<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockReservation;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function adjust(Product $product, int $delta, string $reason, string $key, int $actorId): Product
    {
        return DB::transaction(function () use ($product, $delta, $reason, $key, $actorId) {
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $businessKey = 'adjust:'.$actorId.':'.$product->id.':'.$key;
            $existing = InventoryMovement::where('business_key', $businessKey)->first();
            if ($existing) {
                if ($existing->delta_on_hand !== $delta || $existing->reason !== $reason) {
                    throw new BusinessException('IDEMPOTENCY_CONFLICT', 'Request key was used with different adjustment data.');
                }

                return $product;
            }
            $next = $product->stock_on_hand + $delta;
            if ($next < $product->stock_reserved || $next > 2147483647 || $delta === 0 || trim($reason) === '') {
                throw new BusinessException('INVALID_STOCK_ADJUSTMENT', 'Adjustment must retain all reserved stock and valid counters.');
            }
            $product->stock_on_hand = $next;
            $product->save();
            $this->movement($product, 'adjustment', $delta, 0, $businessKey, $reason, actorId: $actorId);

            return $product;
        }, 3);
    }

    public function reserve(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->lifecycle !== 'open' || now()->gte($order->expires_at)) {
                throw new BusinessException('ORDER_CLOSED', 'Order cannot reserve stock.');
            }
            $items = $order->items()->orderBy('product_id')->orderBy('id')->get();
            $products = Product::whereIn('id', $items->pluck('product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($items as $item) {
                $existing = StockReservation::where('order_item_id', $item->id)->lockForUpdate()->first();
                if ($existing) {
                    if ($existing->status !== 'active') {
                        throw new BusinessException('RESERVATION_CLOSED', 'Closed reservations cannot be reopened.');
                    }

                    continue;
                }
                $product = $products[$item->product_id];
                if ($product->stock_on_hand - $product->stock_reserved < $item->quantity) {
                    throw new BusinessException('INSUFFICIENT_STOCK', 'A product no longer has enough available stock.');
                }
                $product->stock_reserved += $item->quantity;
                $product->save();
                StockReservation::create(['order_item_id' => $item->id, 'product_id' => $item->product_id, 'quantity' => $item->quantity, 'status' => 'active', 'expires_at' => $order->expires_at]);
                $this->movement($product, 'reserve', 0, $item->quantity, 'item:'.$item->id.':reserve', 'Order reservation', $order->id);
            }
        }, 3);
    }

    public function consume(Order $order): void
    {
        $this->settle($order, true);
    }

    public function release(Order $order): void
    {
        $this->settle($order, false);
    }

    private function settle(Order $order, bool $consume): void
    {
        DB::transaction(function () use ($order, $consume) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $items = $order->items()->orderBy('product_id')->orderBy('id')->get();
            $products = Product::whereIn('id', $items->pluck('product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $reservations = StockReservation::whereIn('order_item_id', $items->pluck('id'))->orderBy('product_id')->lockForUpdate()->get();
            if ($consume && $reservations->count() !== $items->count()) {
                throw new BusinessException('RESERVATION_MISSING', 'Order stock is not fully reserved.');
            }
            foreach ($reservations as $reservation) {
                if ($reservation->status !== 'active') {
                    if ($consume && $reservation->status === 'released') {
                        throw new BusinessException('RESERVATION_CLOSED', 'Released stock cannot be consumed.');
                    }

                    continue;
                }
                $product = $products[$reservation->product_id];
                $quantity = $reservation->quantity;
                $product->stock_reserved -= $quantity;
                $product->stock_on_hand -= $consume ? $quantity : 0;
                $product->save();
                $reservation->update(['status' => $consume ? 'consumed' : 'released', 'released_at' => $consume ? null : now()]);
                $kind = $consume ? 'sale' : 'release';
                $this->movement($product, $kind, $consume ? -$quantity : 0, -$quantity, 'item:'.$reservation->order_item_id.':'.$kind, 'Order '.$kind, $order->id);
            }
        }, 3);
    }

    private function movement(Product $product, string $kind, int $onHand, int $reserved, string $key, string $reason, ?int $orderId = null, ?int $actorId = null): void
    {
        InventoryMovement::create([
            'product_id' => $product->id, 'order_id' => $orderId, 'actor_id' => $actorId, 'kind' => $kind,
            'delta_on_hand' => $onHand, 'delta_reserved' => $reserved,
            'resulting_on_hand' => $product->stock_on_hand, 'resulting_reserved' => $product->stock_reserved,
            'reason' => $reason, 'business_key' => $key,
        ]);
    }
}
