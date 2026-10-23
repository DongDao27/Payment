<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_delivery_is_25000_and_pickup_remains_zero_with_server_totals(): void
    {
        $this->fillCart();
        $this->assertSame(25000, (int) config('store.delivery_fee_vnd'));
        $delivery = $this->postJson('/api/v1/checkout/quotes', $this->input('delivery'))->assertCreated()
            ->assertJsonPath('data.totals.shipping_fee', 25000)->assertJsonPath('data.totals.payable_total', 95000);
        $order = $this->postJson('/api/v1/orders', ['quote_id' => $delivery->json('data.id')], ['Idempotency-Key' => 'default-fee-checkout'])->assertCreated();
        $order->assertJsonPath('data.totals.shipping_fee', 25000)->assertJsonPath('data.totals.payable_total', 95000);
        $this->fillCart();
        $this->postJson('/api/v1/checkout/quotes', $this->input('pickup'))->assertCreated()->assertJsonPath('data.totals.shipping_fee', 0)->assertJsonPath('data.totals.payable_total', 70000);
    }

    public function test_blank_shipping_falls_back_but_client_fee_override_is_rejected(): void
    {
        $this->fillCart();
        config(['store.delivery_fee_vnd' => '']);
        $this->postJson('/api/v1/checkout/quotes', $this->input('delivery'))->assertCreated()->assertJsonPath('data.totals.shipping_fee', 25000);
        $this->postJson('/api/v1/checkout/quotes', [...$this->input('delivery'), 'shipping_fee' => 1])->assertUnprocessable();
    }

    public function test_delivery_fee_change_after_quote_requires_reconfirmation(): void
    {
        $this->fillCart();
        $quote = $this->postJson('/api/v1/checkout/quotes', $this->input('delivery'))->assertCreated()->json('data.id');
        config(['store.delivery_fee_vnd' => 30000]);
        $this->postJson('/api/v1/orders', ['quote_id' => $quote], ['Idempotency-Key' => 'changed-fee-checkout'])->assertConflict()->assertJsonPath('error.code', 'PRICE_CHANGED');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('stock_reservations', 0);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_deferred_payment_never_creates_attempt_or_marks_order_paid(): void
    {
        $user = $this->fillCart();
        $quoteId = $this->postJson('/api/v1/checkout/quotes', $this->input('delivery'))->assertCreated()->json('data.id');
        $result = app(CheckoutService::class)->create($user, $quoteId, 'deferred-order-key');
        $code = $result['data']['order_code'];
        $this->postJson('/api/v1/orders/'.$code.'/payment-attempts', [], ['Idempotency-Key' => 'deferred-payment-key'])
            ->assertStatus(503)->assertJsonPath('error.code', 'PAYMENT_INTEGRATION_DEFERRED');
        $this->assertDatabaseCount('payment_attempts', 0);
        $this->assertDatabaseCount('payment_receipts', 0);
        $this->getJson('/api/v1/orders/'.$code)->assertOk()->assertJsonPath('data.payment_status', 'unpaid');
    }

    public function test_no_refunds_policy_overrides_legacy_enable_toggle_and_keeps_data_intact(): void
    {
        $user = $this->fillCart();
        $quoteId = $this->postJson('/api/v1/checkout/quotes', $this->input('pickup'))->assertCreated()->json('data.id');
        $code = app(CheckoutService::class)->create($user, $quoteId, 'no-refund-order-key')['data']['order_code'];
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        config(['store.refund_records_enabled' => true]);
        $this->postJson('/api/v1/admin/orders/'.$code.'/refund-records', [
            'receipt_id' => 1, 'external_reference' => 'policy-test', 'amount' => 1, 'merchandise_amount' => 1,
            'shipping_amount' => 0, 'refunded_at' => now()->subSecond()->toISOString(), 'reason' => 'Policy verification',
            'evidence_reference' => 'evidence.txt',
        ])->assertForbidden()->assertJsonPath('error.code', 'REFUNDS_NOT_SUPPORTED');
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_admin_boolean_filter_accepts_openapi_true_and_false_strings(): void
    {
        Product::factory()->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->getJson('/api/v1/admin/products?is_active=true')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.is_active', true);
        $this->getJson('/api/v1/admin/products?is_active=false')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.is_active', false);
        $this->getJson('/api/v1/admin/products?is_active=invalid')->assertUnprocessable();
    }

    private function fillCart(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $product = Product::factory()->create();
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertOk();

        return $user;
    }

    private function input(string $method): array
    {
        $input = ['fulfillment_method' => $method, 'recipient_name' => 'Policy fixture', 'recipient_phone' => '+84912345678'];
        if ($method === 'delivery') {
            $input['address'] = ['address_line' => 'Test street', 'locality' => 'Test locality', 'province' => 'Test province'];
        }

        return $input;
    }
}
