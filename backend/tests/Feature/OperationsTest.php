<?php

namespace Tests\Feature;

use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\ReconciliationCase;
use App\Models\User;
use App\Payments\PaymentGateway;
use App\Payments\VerifiedPayment;
use App\Services\InventoryService;
use App\Services\ReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\Support\OrderFixture;
use Tests\TestCase;

class OperationsTest extends TestCase
{
    use OrderFixture, RefreshDatabase;

    public function test_expiry_releases_once_at_deadline_and_never_releases_paid_order(): void
    {
        $this->freezeTime();
        $product = Product::factory()->create(['stock_on_hand' => 2]);
        $order = $this->orderFor([$product]);
        $order->update(['expires_at' => now()->addSeconds(1)]);
        app(InventoryService::class)->reserve($order);
        $this->artisan('orders:expire')->assertSuccessful();
        $this->assertSame('open', $order->fresh()->lifecycle);
        $this->travel(1)->seconds();
        $this->artisan('orders:expire')->assertSuccessful();
        $this->artisan('orders:expire')->assertSuccessful();
        $this->assertSame('expired', $order->fresh()->lifecycle);
        $this->assertSame(0, $product->fresh()->stock_reserved);
        $this->assertDatabaseCount('inventory_movements', 2);
        $paid = $this->orderFor([$product]);
        app(InventoryService::class)->reserve($paid);
        app(InventoryService::class)->consume($paid);
        $paid->update(['payment_status' => 'paid', 'expires_at' => now()->subMinute()]);
        $this->artisan('orders:expire')->assertSuccessful();
        $this->assertSame('open', $paid->fresh()->lifecycle);
        $this->assertSame(1, $product->fresh()->stock_on_hand);
    }

    public function test_unknown_provider_keeps_reservation_and_opens_case_with_query_outside_locks(): void
    {
        $product = Product::factory()->create();
        $order = $this->orderFor([$product]);
        app(InventoryService::class)->reserve($order);
        $order->update(['expires_at' => now()->subSecond(), 'payment_status' => 'pending']);
        PaymentAttempt::create(['order_id' => $order->id, 'provider' => 'vnpay', 'provider_reference' => 'unknown-ref', 'requested_amount' => $order->payable_total, 'status' => 'pending', 'expires_at' => $order->expires_at]);
        $gateway = Mockery::mock(PaymentGateway::class);
        $gateway->shouldReceive('query')->once()->andReturnUsing(function () {
            $this->assertSame(1, DB::transactionLevel()); // Test wrapper only, no service transaction.

            return null;
        });
        $this->app->instance(PaymentGateway::class, $gateway);
        $this->artisan('orders:expire')->assertSuccessful();
        $this->artisan('orders:expire')->assertSuccessful();
        $this->assertSame('open', $order->fresh()->lifecycle);
        $this->assertSame('reconciliation_required', $order->fresh()->payment_status);
        $this->assertSame(1, $product->fresh()->stock_reserved);
        $this->assertDatabaseHas('payment_attempts', ['provider_reference' => 'unknown-ref', 'status' => 'unknown']);
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_pickup_can_prepare_unpaid_but_fulfillment_requires_payment_and_current_version(): void
    {
        $order = $this->orderFor([Product::factory()->create()]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $url = '/api/v1/admin/orders/'.$order->order_code.'/fulfillment';
        $this->patchJson($url, ['target_status' => 'preparing', 'version' => 1])->assertOk()->assertJsonPath('data.version', 2);
        $this->patchJson($url, ['target_status' => 'ready_for_pickup', 'version' => 1])->assertConflict();
        $this->patchJson($url, ['target_status' => 'ready_for_pickup', 'version' => 2])->assertOk();
        $this->patchJson($url, ['target_status' => 'fulfilled', 'version' => 3])->assertConflict()->assertJsonPath('error.code', 'PAYMENT_REQUIRED');
        // Test fixture represents separately verified payment, never an API override.
        $order->update(['payment_status' => 'paid']);
        $this->patchJson($url, ['target_status' => 'fulfilled', 'version' => 3])->assertOk()->assertJsonPath('data.lifecycle', 'completed');
        $this->assertNotNull($order->fresh()->completed_at);
        $this->patchJson($url, ['target_status' => 'preparing', 'version' => 4])->assertConflict();
    }

    public function test_case_requires_version_and_verified_evidence_manual_review_does_not_resolve(): void
    {
        $order = $this->orderFor([Product::factory()->create()]);
        $case = ReconciliationCase::create(['order_id' => $order->id, 'reason' => 'provider_query_uncertain', 'status' => 'open']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $url = '/api/v1/admin/reconciliation-cases/'.$case->id;
        $this->patchJson($url, ['resolution' => 'verified_unpaid', 'note' => 'Reviewed manually', 'version' => 1])->assertUnprocessable();
        $this->patchJson($url, ['resolution' => 'manual_review', 'note' => 'Still checking provider', 'version' => 1])->assertOk()->assertJsonPath('data.status', 'open');
        $this->patchJson($url, ['resolution' => 'verified_unpaid', 'note' => 'Reviewed manually', 'evidence_reference' => 'ticket-123', 'version' => 1])->assertConflict();
        $this->getJson('/api/v1/admin/reconciliation-cases?status=open')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_admin_order_filters_and_customer_permissions(): void
    {
        $order = $this->orderFor([Product::factory()->create()]);
        $this->actingAs($order->user)->getJson('/api/v1/admin/orders')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->getJson('/api/v1/admin/orders?fulfillment_method=pickup&q='.$order->order_code)->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/admin/orders/'.$order->order_code)->assertOk()->assertJsonPath('data.order_code', $order->order_code);
    }

    public function test_uncertain_payment_only_releases_after_signed_final_query_and_case_resolution(): void
    {
        $product = Product::factory()->create();
        $order = $this->orderFor([$product]);
        app(InventoryService::class)->reserve($order);
        $order->update(['payment_status' => 'pending', 'expires_at' => now()->subSecond()]);
        $attempt = PaymentAttempt::create(['order_id' => $order->id, 'provider' => 'vnpay', 'provider_reference' => 'final-query-ref', 'requested_amount' => $order->payable_total, 'status' => 'pending', 'expires_at' => $order->expires_at]);
        $failure = new VerifiedPayment($attempt->provider_reference, 'failed', $order->payable_total, null, null, ['fixture' => true], hash('sha256', 'final-failure'));
        $gateway = Mockery::mock(PaymentGateway::class);
        $gateway->shouldReceive('query')->twice()->andReturn(null, $failure);
        $this->app->instance(PaymentGateway::class, $gateway);
        $service = app(ReconciliationService::class);
        $service->close($order->fresh());
        $this->assertSame('reconciliation_required', $order->fresh()->payment_status);
        $this->travel(5)->minutes();
        $service->refresh($order->fresh());
        $this->assertSame('failed', $attempt->fresh()->status);
        $this->assertSame(1, $product->fresh()->stock_reserved);
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (ReconciliationCase::where('order_id', $order->id)->get() as $case) {
            $service->resolve($case, ['resolution' => 'verified_unpaid', 'note' => 'Signed provider final query checked', 'evidence_reference' => 'fixture-query-evidence', 'version' => $case->version], $admin->id);
        }
        $this->assertSame('unpaid', $order->fresh()->payment_status);
        $service->close($order->fresh());
        $this->assertSame('expired', $order->fresh()->lifecycle);
        $this->assertSame(0, $product->fresh()->stock_reserved);
    }
}
