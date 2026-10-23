<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_pickup_checkout_reserves_snapshot_and_clears_cart_with_exact_deadline(): void
    {
        [$user, $product, $quote] = $this->prepare();
        $response = $this->submit($quote)->assertCreated()->assertJsonPath('data.payment_status', 'unpaid')->assertJsonPath('meta.replayed', false);
        $order = Order::firstOrFail();
        $this->assertTrue($order->expires_at->equalTo($order->created_at->addSeconds(86400)));
        $this->assertSame(2, $product->fresh()->stock_reserved);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertNotNull($quote->fresh()->consumed_at);
        $product->update(['name' => 'Changed', 'list_price' => 90000]);
        $this->getJson('/api/v1/orders/'.$response->json('data.order_code'))->assertOk()->assertJsonPath('data.items.0.charged_unit_price', 35000)->assertJsonPath('data.totals.payable_total', 70000);
    }

    public function test_real_checkout_accepts_multiple_products_beyond_the_figma_demo_sku(): void
    {
        $user = User::factory()->create();
        $first = Product::factory()->create(['sku' => 'AUDIT-FIRST', 'list_price' => 14000, 'sale_price' => null, 'stock_on_hand' => 8]);
        $second = Product::factory()->create(['sku' => 'AUDIT-SECOND', 'list_price' => 20500, 'sale_price' => null, 'stock_on_hand' => 9]);
        $this->actingAs($user);
        $this->postJson('/api/v1/cart/items', ['product_id' => $first->id, 'quantity' => 2])->assertOk();
        $this->postJson('/api/v1/cart/items', ['product_id' => $second->id, 'quantity' => 3])->assertOk()->assertJsonCount(2, 'data.items');
        $quote = $this->postJson('/api/v1/checkout/quotes', [
            'fulfillment_method' => 'delivery', 'recipient_name' => 'Test recipient', 'recipient_phone' => '0912345678',
            'address' => ['address_line' => 'Test street', 'locality' => 'Test locality', 'province' => 'Test province'],
        ])->assertCreated()->assertJsonPath('data.totals.payable_total', 114500)->json('data.id');
        $this->postJson('/api/v1/orders', ['quote_id' => $quote], ['Idempotency-Key' => 'multi-product-audit-key'])
            ->assertCreated()->assertJsonCount(2, 'data.items')->assertJsonPath('data.totals.payable_total', 114500);
        $this->assertDatabaseHas('order_items', ['product_id' => $first->id, 'quantity' => 2, 'charged_unit_price' => 14000]);
        $this->assertDatabaseHas('order_items', ['product_id' => $second->id, 'quantity' => 3, 'charged_unit_price' => 20500]);
        $this->assertSame(2, $first->fresh()->stock_reserved);
        $this->assertSame(3, $second->fresh()->stock_reserved);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_delivery_hold_is_exactly_300_seconds(): void
    {
        config(['store.delivery_fee_vnd' => 10000]);
        [$user, $product, $quote] = $this->prepare(method: 'delivery');
        $this->submit($quote)->assertCreated()->assertJsonPath('data.totals.payable_total', 80000);
        $order = Order::firstOrFail();
        $this->assertTrue($order->expires_at->equalTo($order->created_at->addSeconds(300)));
    }

    public function test_replay_works_after_cart_clear_and_quote_expiry_but_new_payload_conflicts(): void
    {
        [$user, $product, $quote] = $this->prepare();
        $first = $this->submit($quote)->assertCreated()->json('data');
        $this->travel(10)->minutes();
        $this->submit($quote)->assertOk()->assertJsonPath('meta.replayed', true)->assertJsonPath('data.order_code', $first['order_code']);
        $this->postJson('/api/v1/orders', ['quote_id' => (string) Str::uuid()], ['Idempotency-Key' => 'checkout-test-key'])->assertConflict()->assertJsonPath('error.code', 'IDEMPOTENCY_CONFLICT');
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_expired_changed_cart_changed_prices_and_reused_quote_do_not_create_orders(): void
    {
        [$user, $product, $quote] = $this->prepare();
        $product->update(['list_price' => 40000, 'catalog_version' => 2]);
        $this->submit($quote)->assertConflict()->assertJsonPath('error.code', 'PRICE_CHANGED');
        $this->assertDatabaseCount('orders', 0);
        $product->update(['list_price' => 35000, 'catalog_version' => 1]);
        $this->patchJson('/api/v1/cart/items/'.CartItem::first()->id, ['quantity' => 1])->assertOk();
        $this->submit($quote)->assertConflict()->assertJsonPath('error.code', 'CART_CHANGED');
        $this->patchJson('/api/v1/cart/items/'.CartItem::first()->id, ['quantity' => 2])->assertOk();
        $this->travel(5)->minutes();
        $this->submit($quote)->assertConflict()->assertJsonPath('error.code', 'QUOTE_EXPIRED');
        $this->assertDatabaseCount('stock_reservations', 0);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_other_user_cannot_read_cancel_or_checkout_anothers_order_or_quote(): void
    {
        [$user, $product, $quote] = $this->prepare();
        $code = $this->submit($quote)->assertCreated()->json('data.order_code');
        $this->actingAs(User::factory()->create());
        $this->getJson('/api/v1/orders/'.$code)->assertNotFound();
        $this->postJson('/api/v1/orders/'.$code.'/cancel')->assertNotFound();
        $this->submit($quote)->assertNotFound();
        $this->getJson('/api/v1/orders')->assertOk()->assertJsonCount(0, 'data');
        $this->assertSame(2, $product->fresh()->stock_reserved);
    }

    public function test_cancellation_releases_exactly_once_and_does_not_allow_consuming_closed_stock(): void
    {
        [$user, $product, $quote] = $this->prepare();
        $code = $this->submit($quote)->assertCreated()->json('data.order_code');
        $this->postJson('/api/v1/orders/'.$code.'/cancel')->assertOk()->assertJsonPath('data.lifecycle', 'cancelled');
        $this->postJson('/api/v1/orders/'.$code.'/cancel')->assertOk();
        $this->assertSame(0, $product->fresh()->stock_reserved);
        $this->assertSame(10, $product->fresh()->stock_on_hand);
        $this->assertDatabaseCount('inventory_movements', 2);
        $this->submit($quote, 'another-checkout-key')->assertConflict()->assertJsonPath('error.code', 'QUOTE_CONSUMED');
    }

    public function test_mid_reservation_exception_rolls_back_order_items_stock_quote_and_cart(): void
    {
        [$user, $product, $quote] = $this->prepare();
        $second = Product::factory()->create();
        app(CartService::class)->add($user, $second->id, 1);
        $quote = app(QuoteService::class)->create($user, $quote->payload);
        InventoryMovement::creating(function ($movement) use ($second) {
            if ($movement->product_id === $second->id) {
                throw new \RuntimeException('Injected transaction failure');
            }
        });
        try {
            app(CheckoutService::class)->create($user, $quote->id, 'rollback-test-key');
            $this->fail('Injected failure was swallowed.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Injected transaction failure', $exception->getMessage());
        } finally {
            InventoryMovement::flushEventListeners();
        }
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('stock_reservations', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('idempotency_keys', 0);
        $this->assertDatabaseCount('cart_items', 2);
        $this->assertNull($quote->fresh()->consumed_at);
        $this->assertSame(0, $product->fresh()->stock_reserved);
    }

    private function prepare(string $method = 'pickup'): array
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = Product::factory()->create();
        app(CartService::class)->add($user, $product->id, 2);
        $input = ['fulfillment_method' => $method, 'recipient_name' => 'Test recipient', 'recipient_phone' => '+84912345678'];
        if ($method === 'delivery') {
            $input['address'] = ['address_line' => 'Test street', 'locality' => 'Test locality', 'province' => 'Test province'];
        }

        return [$user, $product, app(QuoteService::class)->create($user, $input)];
    }

    public function test_reorder_merges_with_current_cart_and_prices_without_creating_an_order_or_reservation(): void
    {
        [$user, $product, $quote] = $this->prepare();
        $code = $this->submit($quote)->assertCreated()->json('data.order_code');
        $this->postJson('/api/v1/orders/'.$code.'/cancel')->assertOk();
        $product->update(['list_price' => 50000]);
        app(CartService::class)->add($user, $product->id, 1);
        $this->postJson('/api/v1/orders/'.$code.'/reorder')->assertOk()->assertJsonPath('data.items.0.quantity', 3)->assertJsonPath('data.estimated_total', 150000);
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(0, $product->fresh()->stock_reserved);
    }

    private function submit(Quote $quote, string $key = 'checkout-test-key')
    {
        return $this->postJson('/api/v1/orders', ['quote_id' => $quote->id], ['Idempotency-Key' => $key]);
    }
}
