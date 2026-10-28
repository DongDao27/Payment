<?php

namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\OrderStatusHistory;
use App\Models\PaymentAttempt;
use App\Models\PaymentReceipt;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\OrderFixture;
use Tests\TestCase;

class AdminHistoryTest extends TestCase
{
    use OrderFixture, RefreshDatabase;

    public function test_history_is_admin_only_and_inventory_includes_server_prices(): void
    {
        $product = Product::factory()->create(['list_price' => 35000, 'sale_price' => 33250]);
        $order = $this->orderFor([$product]);
        $urls = ['/api/v1/admin/inventory/'.$product->id.'/movements', '/api/v1/admin/orders/'.$order->order_code.'/history'];
        foreach ($urls as $url) {
            $this->getJson($url)->assertUnauthorized();
        }
        $this->actingAs($order->user);
        foreach ($urls as $url) {
            $this->getJson($url)->assertForbidden();
        }
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->getJson('/api/v1/admin/inventory')->assertOk()
            ->assertJsonPath('data.0.list_price', 35000)->assertJsonPath('data.0.sale_price', 33250)
            ->assertJsonPath('data.0.unit_price', 33250);
        foreach ($urls as $url) {
            $this->getJson($url)->assertOk()->assertJsonCount(0, 'data');
        }
    }

    public function test_inventory_ledger_is_product_scoped_paginated_and_read_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['stock_on_hand' => 10]);
        $other = Product::factory()->create();
        $service = app(InventoryService::class);
        $service->adjust($other, 1, 'Other product', 'other-adjustment', $admin->id);
        foreach ([1, 2, 3] as $delta) {
            $service->adjust($product, $delta, 'Stock check '.$delta, 'adjust-'.$delta, $admin->id);
        }
        $this->actingAs($admin);
        $url = '/api/v1/admin/inventory/'.$product->id.'/movements?per_page=2';
        $first = $this->getJson($url)->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.pagination.total', 3)->assertJsonPath('meta.pagination.last_page', 2)
            ->assertJsonPath('data.0.reason', 'Stock check 3')->assertJsonPath('data.0.actor_name', $admin->name);
        $second = $this->getJson($url.'&page=2')->assertOk()->assertJsonCount(1, 'data');
        $ids = [...array_column($first->json('data'), 'id'), ...array_column($second->json('data'), 'id')];
        $this->assertCount(3, array_unique($ids));
        $this->assertStringNotContainsString('business_key', $first->getContent());
        $this->assertSame(16, $product->fresh()->stock_on_hand);
        $this->assertSame(4, InventoryMovement::count());
        $this->getJson($url.'&page=0')->assertUnprocessable();
        $this->getJson('/api/v1/admin/inventory/'.$product->id.'/movements?per_page=51')->assertUnprocessable();
    }

    public function test_payment_history_is_order_scoped_and_never_returns_provider_payload_or_signatures(): void
    {
        $product = Product::factory()->create();
        $order = $this->orderFor([$product]);
        $other = $this->orderFor([$product]);
        foreach ([$other, $order] as $current) {
            $attempt = PaymentAttempt::create([
                'order_id' => $current->id, 'provider' => 'vnpay', 'provider_reference' => 'private-reference-'.$current->id,
                'requested_amount' => $current->payable_total, 'status' => 'succeeded', 'expires_at' => $current->expires_at,
            ]);
            PaymentReceipt::create([
                'provider' => 'vnpay', 'provider_transaction_id' => 'TX-'.$current->id, 'order_id' => $current->id,
                'attempt_id' => $attempt->id, 'received_amount' => $current->payable_total,
                'provider_paid_at' => now(), 'verified_at' => now(), 'allocation' => 'accepted',
            ]);
            OrderStatusHistory::create([
                'order_id' => $current->id, 'event' => 'payment_verified',
                'before' => ['payment_status' => 'pending', 'secret_payload' => 'must-not-leak'],
                'after' => ['payment_status' => 'paid', 'email' => 'private@example.test'],
            ]);
        }
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $url = '/api/v1/admin/orders/'.$order->order_code.'/history';
        $attempts = $this->getJson($url)->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'attempt')->assertJsonPath('data.0.status', 'succeeded');
        $this->assertStringNotContainsString('private-reference', $attempts->getContent());
        $this->getJson($url.'?type=receipts')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'receipt')->assertJsonPath('data.0.transaction_id', 'TX-'.$order->id)
            ->assertJsonPath('data.0.amount', $order->payable_total)->assertJsonPath('data.0.status', 'accepted');
        $statuses = $this->getJson($url.'?type=statuses')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.before.payment_status', 'pending')->assertJsonPath('data.0.after.payment_status', 'paid');
        $this->assertStringNotContainsString('must-not-leak', $statuses->getContent());
        $this->assertStringNotContainsString('private@example.test', $statuses->getContent());
        $this->getJson($url.'?type=raw-payload')->assertUnprocessable();
        $this->getJson($url.'?per_page=100')->assertUnprocessable();
        $this->getJson('/api/v1/admin/orders/not-an-order/history')->assertNotFound();
        $this->assertSame(2, PaymentReceipt::count());
    }

    public function test_order_history_pagination_does_not_hide_older_attempts(): void
    {
        $order = $this->orderFor([Product::factory()->create()]);
        foreach (range(1, 4) as $n) {
            PaymentAttempt::create([
                'order_id' => $order->id, 'provider' => 'vnpay', 'provider_reference' => 'attempt-'.$n,
                'requested_amount' => $order->payable_total, 'status' => 'failed', 'expires_at' => $order->expires_at,
            ]);
        }
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $ids = [];
        foreach ([1, 2] as $page) {
            $response = $this->getJson('/api/v1/admin/orders/'.$order->order_code.'/history?per_page=2&page='.$page)
                ->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.pagination.total', 4);
            $ids = [...$ids, ...array_column($response->json('data'), 'id')];
        }
        $this->assertCount(4, array_unique($ids));
    }
}
