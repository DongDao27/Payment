<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\PaymentAttempt;
use App\Models\PaymentReceipt;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\OrderFixture;
use Tests\TestCase;

class SalesHomeArchiveTest extends TestCase
{
    use OrderFixture, RefreshDatabase;

    public function test_bestsellers_rank_real_paid_quantities_before_curated_products_without_counting_unpaid_or_duplicate_receipts(): void
    {
        $flagged = Product::factory()->create(['is_bestseller' => true]);
        $winner = Product::factory()->create(['is_bestseller' => false]);
        $runner = Product::factory()->create();
        $this->orderFor([$flagged]);
        foreach ([[$winner, 4], [$runner, 2]] as [$product, $quantity]) {
            $order = $this->orderFor([$product]);
            $order->items()->update(['quantity' => $quantity, 'line_total' => $product->list_price * $quantity]);
            $order->update(['payment_status' => 'paid', 'paid_at' => now(), 'items_subtotal' => $product->list_price * $quantity, 'payable_total' => $product->list_price * $quantity]);
            $attempt = PaymentAttempt::create(['order_id' => $order->id, 'provider' => 'fixture', 'provider_reference' => 'fixture-'.$order->id, 'requested_amount' => $order->payable_total, 'expires_at' => $order->expires_at]);
            foreach (['accepted', 'duplicate'] as $allocation) {
                PaymentReceipt::create(['provider' => 'fixture', 'provider_transaction_id' => $allocation.$order->id, 'order_id' => $order->id, 'attempt_id' => $attempt->id, 'received_amount' => $order->payable_total, 'provider_paid_at' => now(), 'verified_at' => now(), 'allocation' => $allocation]);
            }
        }
        $this->getJson('/api/v1/home')->assertOk()->assertJsonCount(3, 'data.bestseller')->assertJsonPath('data.bestseller.0.id', $winner->id)->assertJsonPath('data.bestseller.0.sold_count', 4)->assertJsonPath('data.bestseller.1.sold_count', 2)->assertJsonPath('data.bestseller.2.id', $flagged->id)->assertJsonPath('data.bestseller.2.sold_count', 0);
        $this->getJson('/api/v1/products?section=bestseller&category_id='.$winner->category_id)->assertOk()->assertJsonPath('data.0.id', $winner->id);
        $winner->update(['is_active' => false]);
        $this->getJson('/api/v1/home')->assertJsonCount(2, 'data.bestseller');
    }

    public function test_new_and_recommended_are_available_diverse_and_sales_empty_without_paid_orders(): void
    {
        $categories = Category::factory()->count(3)->create();
        foreach ($categories as $category) {
            Product::factory()->count(6)->create(['category_id' => $category->id]);
        }
        $latest = Product::factory()->create(['category_id' => $categories[0]->id]);
        $hidden = Product::factory()->create(['is_active' => false]);
        $response = $this->getJson('/api/v1/home')->assertOk()->assertJsonCount(0, 'data.bestseller')->assertJsonPath('data.new.0.id', $latest->id)->assertJsonCount(12, 'data.recommended');
        $this->assertCount(3, array_unique(array_map(fn ($product) => $product['category']['id'], array_slice($response->json('data.recommended'), 0, 3))));
        $this->assertNotContains($hidden->id, array_column($response->json('data.recommended'), 'id'));
    }

    public function test_archive_is_admin_only_version_checked_idempotent_and_preserves_order_and_stock(): void
    {
        $product = Product::factory()->create();
        $order = $this->orderFor([$product]);
        $url = '/api/v1/admin/products/'.$product->id;
        $this->actingAs(User::factory()->create())->deleteJson($url, ['version' => 1])->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->deleteJson($url, ['version' => 999])->assertConflict();
        $this->deleteJson($url, ['version' => 1])->assertNoContent();
        $this->deleteJson($url, ['version' => 1])->assertNoContent();
        $this->assertSame(2, $product->fresh()->catalog_version);
        $this->assertFalse($product->fresh()->is_active);
        $this->assertSame($product->stock_on_hand, $product->fresh()->stock_on_hand);
        $this->assertSame($product->name, $order->items()->first()->name);
        $this->getJson('/api/v1/products/'.$product->id)->assertNotFound();
        $this->patchJson($url, ['is_active' => true, 'version' => 2])->assertOk();
        $this->deleteJson($url, ['version' => 1])->assertConflict();
    }

    public function test_approved_revenue_reports_empty_days_with_zero_shipping_separate(): void
    {
        config(['store.revenue_policy' => 'approved_v1']);
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->getJson('/api/v1/admin/revenue?from=2026-10-01&to=2026-10-04&granularity=day')
            ->assertOk()->assertJsonCount(3, 'data.series')->assertJsonPath('data.totals.accepted_sales', 0)
            ->assertJsonPath('data.totals.shipping_collected', 0)->assertJsonPath('data.series.2.period', '2026-10-03');
    }

    public function test_recommendations_prefer_only_the_current_customers_cart_categories(): void
    {
        $mine = Product::factory()->create();
        $theirs = Product::factory()->create();
        $customer = User::factory()->create();
        $other = User::factory()->create();
        foreach ([[$customer, $mine], [$other, $theirs]] as [$user, $product]) {
            $cart = Cart::create(['user_id' => $user->id]);
            $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);
        }
        $this->actingAs($customer)->getJson('/api/v1/home')->assertOk()->assertJsonPath('data.recommended.0.id', $mine->id);
        $this->actingAs($other)->getJson('/api/v1/home')->assertOk()->assertJsonPath('data.recommended.0.id', $theirs->id);
    }

    public function test_revenue_counts_merchandise_after_discount_and_shipping_separately(): void
    {
        config(['store.revenue_policy' => 'approved_v1']);
        $this->travelTo(now()->setDate(2026, 10, 23)->setTime(8, 0));
        $order = $this->orderFor([Product::factory()->create()]);
        $order->items()->update(['charged_unit_price' => 25000, 'discount_line_total' => 10000, 'line_total' => 25000]);
        $order->update(['items_subtotal' => 35000, 'discount_total' => 10000, 'shipping_fee' => 25000, 'payable_total' => 50000, 'payment_status' => 'paid', 'paid_at' => now(), 'fulfillment_method' => 'delivery', 'address' => ['address_line' => 'Fixture', 'locality' => 'Fixture', 'province' => 'Fixture']]);
        $attempt = PaymentAttempt::create(['order_id' => $order->id, 'provider' => 'fixture', 'provider_reference' => 'discount-fixture', 'requested_amount' => 50000, 'expires_at' => $order->expires_at]);
        PaymentReceipt::create(['provider' => 'fixture', 'provider_transaction_id' => 'discount-txn', 'order_id' => $order->id, 'attempt_id' => $attempt->id, 'received_amount' => 50000, 'provider_paid_at' => now(), 'verified_at' => now(), 'allocation' => 'accepted']);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->getJson('/api/v1/admin/revenue?from=2026-10-23&to=2026-10-24')->assertOk()->assertJsonPath('data.totals.accepted_sales', 25000)->assertJsonPath('data.totals.shipping_collected', 25000)->assertJsonPath('data.totals.unallocated_receipts', 0);
    }
}
