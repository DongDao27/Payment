<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartQuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_post_adds_patch_sets_and_delete_removes_without_reserving_stock(): void
    {
        $product = Product::factory()->create(['stock_on_hand' => 10]);
        $this->getJson('/api/v1/cart')->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $id = $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertOk()->json('data.items.0.id');
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertOk()->assertJsonPath('data.items.0.quantity', 4);
        $this->patchJson('/api/v1/cart/items/'.$id, ['quantity' => 1])->assertOk()->assertJsonPath('data.items.0.quantity', 1);
        $this->assertSame(0, $product->fresh()->stock_reserved);
        $this->deleteJson('/api/v1/cart/items/'.$id)->assertNoContent();
        $this->getJson('/api/v1/cart')->assertOk()->assertJsonCount(0, 'data.items');
    }

    public function test_other_customer_cannot_mutate_cart_item(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create());
        $id = $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertOk()->json('data.items.0.id');
        $this->actingAs(User::factory()->create());
        $this->patchJson('/api/v1/cart/items/'.$id, ['quantity' => 1])->assertNotFound();
        $this->deleteJson('/api/v1/cart/items/'.$id)->assertNotFound();
        $this->assertSame(2, CartItem::find($id)->quantity);
    }

    public function test_cart_shows_unavailable_items_without_silently_removing_them(): void
    {
        $product = $this->fillCart();
        $product->update(['is_active' => false]);
        $this->getJson('/api/v1/cart')->assertOk()->assertJsonPath('data.items.0.is_available', false)->assertJsonCount(1, 'data.warnings');
        $this->postJson('/api/v1/checkout/quotes', $this->pickup())->assertConflict()->assertJsonPath('error.code', 'PRODUCT_UNAVAILABLE');
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_pickup_quote_has_immutable_prices_totals_five_minute_expiry_and_no_reservation(): void
    {
        $this->freezeTime();
        $product = $this->fillCart(['sale_price' => 30000]);
        $response = $this->postJson('/api/v1/checkout/quotes', $this->pickup())->assertCreated()
            ->assertJsonPath('data.totals.items_subtotal', 70000)->assertJsonPath('data.totals.discount_total', 10000)
            ->assertJsonPath('data.totals.shipping_fee', 0)->assertJsonPath('data.totals.payable_total', 60000);
        $quote = Quote::findOrFail($response->json('data.id'));
        $this->assertTrue($quote->expires_at->equalTo(now()->addMinutes(5)));
        $this->assertSame('+84912345678', $quote->payload['recipient_phone']);
        $product->update(['sale_price' => null, 'list_price' => 50000]);
        $this->assertSame(60000, $quote->fresh()->payable_total);
        $this->assertDatabaseCount('stock_reservations', 0);
        $this->assertSame(0, $product->fresh()->stock_reserved);
    }

    public function test_delivery_requires_address_and_uses_default_shipping_when_not_configured(): void
    {
        $this->fillCart();
        $input = [...$this->pickup(), 'fulfillment_method' => 'delivery'];
        $this->postJson('/api/v1/checkout/quotes', $input)->assertUnprocessable();
        $input['address'] = ['address_line' => 'Test street', 'locality' => 'Test locality', 'province' => 'Test province'];
        config(['store.delivery_fee_vnd' => null]);
        $this->postJson('/api/v1/checkout/quotes', $input)->assertCreated()->assertJsonPath('data.totals.shipping_fee', 25000)->assertJsonPath('data.totals.payable_total', 95000);
        config(['store.delivery_fee_vnd' => 'invalid']);
        $this->postJson('/api/v1/checkout/quotes', $input)->assertStatus(503)->assertJsonPath('error.code', 'SHIPPING_NOT_CONFIGURED');
        config(['store.delivery_fee_vnd' => 15000]);
        $this->postJson('/api/v1/checkout/quotes', $input)->assertCreated()->assertJsonPath('data.totals.payable_total', 85000);
    }

    public function test_cart_changes_change_fingerprint_and_price_changes_change_quote_version(): void
    {
        $product = $this->fillCart();
        $before = $this->postJson('/api/v1/checkout/quotes', $this->pickup())->assertCreated()->json('data');
        $product->update(['list_price' => 40000, 'catalog_version' => 2]);
        $price = $this->postJson('/api/v1/checkout/quotes', $this->pickup())->assertCreated()->json('data');
        $this->assertNotSame($before['pricing_version'], $price['pricing_version']);
        $this->patchJson('/api/v1/cart/items/'.CartItem::first()->id, ['quantity' => 1])->assertOk();
        $cart = $this->postJson('/api/v1/checkout/quotes', $this->pickup())->assertCreated()->json('data');
        $this->assertNotSame($before['cart_fingerprint'], $cart['cart_fingerprint']);
    }

    public function test_empty_cart_invalid_quantities_and_client_totals_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $this->postJson('/api/v1/checkout/quotes', $this->pickup())->assertConflict()->assertJsonPath('error.code', 'EMPTY_CART');
        $product = Product::factory()->create(['stock_on_hand' => 100]);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 100])->assertUnprocessable();
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 99])->assertOk();
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertConflict();
        $this->postJson('/api/v1/checkout/quotes', [...$this->pickup(), 'payable_total' => 1])->assertUnprocessable();
    }

    private function fillCart(array $attributes = []): Product
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create($attributes);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertOk();

        return $product;
    }

    private function pickup(): array
    {
        return ['fulfillment_method' => 'pickup', 'recipient_name' => 'Test recipient', 'recipient_phone' => '0912345678'];
    }
}
