<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class QuoteService
{
    public function __construct(private CartService $carts, private PricingService $pricing) {}

    public function create(User $user, array $input): Quote
    {
        return DB::transaction(function () use ($user, $input) {
            $cart = $this->carts->load($this->carts->lockedCart($user));
            $snapshot = $this->snapshot($cart, $input);

            return Quote::create([
                'user_id' => $user->id, 'cart_id' => $cart->id, 'cart_fingerprint' => $this->carts->fingerprint($cart),
                'payload' => $snapshot, ...$snapshot['totals'], 'pricing_version' => $this->version($snapshot),
                'expires_at' => now()->addSeconds(config('store.quote_ttl_seconds')),
            ]);
        }, 3);
    }

    public function snapshot(Cart $cart, array $input, bool $lockProducts = false): array
    {
        if ($cart->items->isEmpty()) {
            throw new BusinessException('EMPTY_CART', 'The cart is empty.');
        }
        $query = Product::with('category')->whereIn('id', $cart->items->pluck('product_id'))->orderBy('id');
        $products = ($lockProducts ? $query->lockForUpdate() : $query)->get()->keyBy('id');
        $items = [];
        $subtotal = $discount = 0;
        foreach ($cart->items as $item) {
            $product = $products[$item->product_id];
            if (! $product->is_active || ! $product->category->is_active) {
                throw new BusinessException('PRODUCT_UNAVAILABLE', 'A cart product is no longer for sale.');
            }
            if ($product->stock_on_hand - $product->stock_reserved < $item->quantity) {
                throw new BusinessException('INSUFFICIENT_STOCK', 'A cart product has insufficient available stock.');
            }
            $unit = $this->pricing->unitPrice($product);
            $lineDiscount = ($product->list_price - $unit) * $item->quantity;
            $items[] = [
                'product_id' => $product->id, 'cart_item_id' => $item->id, 'catalog_version' => $product->catalog_version,
                'sku' => $product->sku, 'name' => $product->name, 'list_unit_price' => $product->list_price,
                'charged_unit_price' => $unit, 'quantity' => $item->quantity, 'line_total' => $unit * $item->quantity,
                'discount_line_total' => $lineDiscount,
            ];
            $subtotal += $product->list_price * $item->quantity;
            $discount += $lineDiscount;
        }
        $shipping = $this->shipping($input['fulfillment_method']);
        $total = $subtotal - $discount + $shipping;
        if ($total > config('store.max_order_vnd')) {
            throw new BusinessException('ORDER_TOTAL_LIMIT', 'Order exceeds the supported maximum total.');
        }

        return [
            'fulfillment_method' => $input['fulfillment_method'], 'recipient_name' => $input['recipient_name'],
            'recipient_phone' => $input['recipient_phone'], 'address' => $input['address'] ?? null, 'items' => $items,
            'totals' => ['items_subtotal' => $subtotal, 'discount_total' => $discount, 'shipping_fee' => $shipping, 'payable_total' => $total],
        ];
    }

    public function version(array $snapshot): int
    {
        // 48-bit positive digest stays within JavaScript's safe integer range.
        return max(1, (int) hexdec(substr(hash('sha256', json_encode([$snapshot['items'], $snapshot['totals']], JSON_THROW_ON_ERROR)), 0, 12)));
    }

    private function shipping(string $method): int
    {
        if ($method === 'pickup') {
            return 0;
        }
        $fee = config('store.delivery_fee_vnd');
        if ($fee === null || $fee === '') {
            $fee = 25000;
        }
        if (filter_var($fee, FILTER_VALIDATE_INT) === false || $fee < 0 || $fee > config('store.max_order_vnd')) {
            throw new BusinessException('SHIPPING_NOT_CONFIGURED', 'Delivery shipping policy is not configured.', 503);
        }

        return (int) $fee;
    }
}
