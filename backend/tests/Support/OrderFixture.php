<?php

namespace Tests\Support;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Str;

trait OrderFixture
{
    protected function orderFor(array $products): Order
    {
        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);
        $total = array_sum(array_map(fn (Product $product) => $product->list_price, $products));
        $quote = Quote::create(['user_id' => $user->id, 'cart_id' => $cart->id, 'cart_fingerprint' => str_repeat('a', 64), 'payload' => [], 'items_subtotal' => $total, 'discount_total' => 0, 'shipping_fee' => 0, 'payable_total' => $total, 'pricing_version' => '1', 'expires_at' => now()->addMinutes(5)]);
        $order = Order::create(['order_code' => (string) Str::ulid(), 'user_id' => $user->id, 'quote_id' => $quote->id, 'fulfillment_method' => 'pickup', 'recipient_name' => 'Test fixture', 'recipient_phone' => '+84912345678', 'items_subtotal' => $total, 'discount_total' => 0, 'shipping_fee' => 0, 'payable_total' => $total, 'expires_at' => now()->addDay()]);
        foreach ($products as $product) {
            $order->items()->create(['product_id' => $product->id, 'sku' => $product->sku, 'name' => $product->name, 'list_unit_price' => $product->list_price, 'charged_unit_price' => $product->list_price, 'quantity' => 1, 'discount_line_total' => 0, 'line_total' => $product->list_price]);
        }

        return $order;
    }
}
