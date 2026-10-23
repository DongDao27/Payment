<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Http\Resources\OrderResource;
use App\Models\IdempotencyKey;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(private CartService $carts, private QuoteService $quotes, private InventoryService $inventory) {}

    public function create(User $user, string $quoteId, string $key): array
    {
        $quoteId = strtolower($quoteId);
        $hash = hash('sha256', $quoteId);

        return DB::transaction(function () use ($user, $quoteId, $key, $hash) {
            // The user row serializes all of this customer's cart and idempotency writes.
            $cart = $this->carts->lockedCart($user);
            $idempotency = IdempotencyKey::where('user_id', $user->id)->where('operation', 'checkout')->where('key', $key)->lockForUpdate()->first();
            if ($idempotency) {
                if ($idempotency->request_hash !== $hash) {
                    throw new BusinessException('IDEMPOTENCY_CONFLICT', 'Request key was already used for another quote.');
                }

                return ['data' => $idempotency->cached_response, 'replayed' => true];
            }
            $idempotency = IdempotencyKey::create(['user_id' => $user->id, 'operation' => 'checkout', 'key' => $key, 'request_hash' => $hash, 'status' => 'processing']);
            $quote = Quote::whereKey($quoteId)->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            if ($quote->consumed_at !== null) {
                throw new BusinessException('QUOTE_CONSUMED', 'The quote has already been used.');
            }
            if (now()->gte($quote->expires_at)) {
                throw new BusinessException('QUOTE_EXPIRED', 'The quote has expired. Request a new quote.');
            }
            $cart = $this->carts->load($cart);
            if ($quote->cart_id !== $cart->id || $quote->cart_fingerprint !== $this->carts->fingerprint($cart)) {
                throw new BusinessException('CART_CHANGED', 'The cart changed. Review a new quote.');
            }
            $payload = $quote->payload;
            $createdAt = now();
            $order = new Order([
                'order_code' => (string) Str::ulid(), 'user_id' => $user->id, 'quote_id' => $quote->id,
                'fulfillment_method' => $payload['fulfillment_method'], 'recipient_name' => $payload['recipient_name'],
                'recipient_phone' => $payload['recipient_phone'], 'address' => $payload['address'], ...$payload['totals'],
                'lifecycle' => 'open', 'payment_status' => 'unpaid', 'fulfillment_status' => 'unprocessed', 'version' => 1,
                'expires_at' => $createdAt->copy()->addSeconds($payload['fulfillment_method'] === 'delivery' ? 300 : 86400),
            ]);
            $order->created_at = $createdAt;
            $order->save();
            // Lock the newly created order before products, shared by subsequent state transitions.
            $snapshot = $this->quotes->snapshot($cart, $payload, lockProducts: true);
            if ((int) $quote->pricing_version !== $this->quotes->version($snapshot) || $payload != $snapshot) {
                throw new BusinessException('PRICE_CHANGED', 'Catalog or shipping pricing changed. Review a new quote.');
            }
            foreach ($payload['items'] as $line) {
                unset($line['catalog_version'], $line['cart_item_id']);
                $order->items()->create($line);
            }
            $this->inventory->reserve($order);
            $quote->update(['consumed_at' => now()]);
            $cart->items()->whereIn('id', array_column($payload['items'], 'cart_item_id'))->delete();
            OrderStatusHistory::create(['order_id' => $order->id, 'actor_id' => $user->id, 'event' => 'created', 'before' => [], 'after' => ['lifecycle' => 'open', 'payment_status' => 'unpaid'], 'request_id' => request()->attributes->get('request_id')]);
            $order->load('items');
            $data = json_decode((new OrderResource($order))->toJson(), true, flags: JSON_THROW_ON_ERROR);
            $idempotency->update(['status' => 'succeeded', 'order_id' => $order->id, 'cached_response' => $data]);

            return ['data' => $data, 'replayed' => false];
        }, 3);
    }
}
