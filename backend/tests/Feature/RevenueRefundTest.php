<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\PaymentReceipt;
use App\Models\Product;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\OrderFixture;
use Tests\TestCase;

class RevenueRefundTest extends TestCase
{
    use OrderFixture, RefreshDatabase;

    public function test_reports_are_policy_gated_and_customer_forbidden(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/api/v1/admin/revenue?from=2026-10-20&to=2026-10-21')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        config(['store.revenue_policy' => 'proposed_not_approved']);
        $this->getJson('/api/v1/admin/revenue?from=2026-10-20&to=2026-10-21')->assertStatus(503)->assertJsonPath('error.code', 'REVENUE_POLICY_NOT_APPROVED');
    }

    public function test_report_uses_vietnam_day_boundaries_and_separates_unallocated_refunds(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 21)->setTime(12, 0));
        config(['store.revenue_policy' => 'approved_v1']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        [$accepted, $receipt] = $this->receipt('accepted', '2026-10-20 17:00:00', 10000);
        [$outside] = $this->receipt('accepted', '2026-10-20 16:59:59', 0);
        [$nextDay] = $this->receipt('accepted', '2026-10-21 17:00:00', 0);
        [$duplicate, $extra] = $this->receipt('duplicate', '2026-10-21 08:00:00', 0);
        $this->refundFixture($accepted, $receipt, $admin, 5000, 2000);
        $this->refundFixture($duplicate, $extra, $admin, 7000, 0);
        $response = $this->getJson('/api/v1/admin/revenue?from=2026-10-21&to=2026-10-22&granularity=day')->assertOk();
        $totals = ['accepted_sales' => 35000, 'merchandise_refunds' => 5000, 'net_sales' => 30000, 'shipping_collected' => 10000, 'shipping_refunds' => 2000, 'unallocated_receipts' => 35000, 'unallocated_refunds' => 7000];
        $this->assertSame($totals, $response->json('data.totals'));
        $this->assertSame('2026-10-21', $response->json('data.series.0.period'));
        foreach ($totals as $key => $amount) {
            $this->assertSame($amount, array_sum(array_column($response->json('data.series'), $key)));
        }
        foreach (['week', 'month'] as $granularity) {
            $this->getJson('/api/v1/admin/revenue?from=2026-10-21&to=2026-10-22&granularity='.$granularity)->assertOk()->assertJsonPath('data.totals.net_sales', 30000);
        }
    }

    public function test_refund_requires_private_evidence_is_capped_and_never_restocks_or_transfers(): void
    {
        Storage::fake('local');
        // Historical ledger behavior remains testable without enabling refunds for this store.
        config(['store.after_sales_policy' => 'historical_fixture']);
        config(['store.refund_records_enabled' => true]);
        [$order, $receipt] = $this->receipt('accepted', now()->subMinute()->format('Y-m-d H:i:s'), 0);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $body = ['receipt_id' => $receipt->id, 'external_reference' => 'verified-refund-1', 'amount' => 20000, 'merchandise_amount' => 20000, 'shipping_amount' => 0, 'refunded_at' => now()->subSecond()->toISOString(), 'reason' => 'Verified external refund', 'evidence_reference' => 'verified.txt'];
        $url = '/api/v1/admin/orders/'.$order->order_code.'/refund-records';
        $this->postJson($url, $body)->assertUnprocessable()->assertJsonPath('error.code', 'EVIDENCE_REQUIRED');
        Storage::disk('local')->put('evidence/verified.txt', 'Test-only verified evidence fixture.');
        $id = $this->postJson($url, $body)->assertCreated()->assertJsonMissingPath('data.evidence_path')->json('data.id');
        $this->postJson($url, $body)->assertConflict();
        $this->postJson($url, [...$body, 'external_reference' => 'verified-refund-2'])->assertConflict()->assertJsonPath('error.code', 'REFUND_AMOUNT_INVALID');
        $this->get('/api/v1/admin/refund-records/'.$id.'/evidence')->assertOk();
        $this->actingAs($order->user)->getJson('/api/v1/admin/refund-records/'.$id.'/evidence')->assertForbidden();
        $this->postJson($url, [...$body, 'evidence_reference' => '../secret'])->assertForbidden();
        $this->assertSame(10, Product::first()->stock_on_hand);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('refunds', 1);
    }

    public function test_refund_write_is_forbidden_by_current_no_refunds_policy(): void
    {
        [$order, $receipt] = $this->receipt('accepted', now()->subMinute()->format('Y-m-d H:i:s'), 0);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        config(['store.refund_records_enabled' => false]);
        $this->postJson('/api/v1/admin/orders/'.$order->order_code.'/refund-records', ['receipt_id' => $receipt->id, 'external_reference' => 'blocked', 'amount' => 1, 'merchandise_amount' => 1, 'shipping_amount' => 0, 'refunded_at' => now()->subSecond()->toISOString(), 'reason' => 'Testing blocked policy', 'evidence_reference' => 'verified.txt'])->assertForbidden()->assertJsonPath('error.code', 'REFUNDS_NOT_SUPPORTED');
        $this->assertDatabaseCount('refunds', 0);
    }

    private function receipt(string $allocation, string $at, int $shipping): array
    {
        $order = $this->orderFor([Product::factory()->create()]);
        $order->update(['payment_status' => 'paid', 'paid_at' => $at, 'fulfillment_method' => $shipping ? 'delivery' : 'pickup', 'address' => $shipping ? ['address_line' => 'Fixture', 'locality' => 'Fixture', 'province' => 'Fixture'] : null, 'shipping_fee' => $shipping, 'payable_total' => 35000 + $shipping]);
        $attempt = PaymentAttempt::create(['order_id' => $order->id, 'provider' => 'fixture', 'provider_reference' => 'fixture-'.$order->id, 'requested_amount' => $order->payable_total, 'expires_at' => $order->expires_at]);
        $receipt = PaymentReceipt::create(['provider' => 'fixture', 'provider_transaction_id' => 'txn-'.$order->id, 'order_id' => $order->id, 'attempt_id' => $attempt->id, 'received_amount' => $order->payable_total, 'provider_paid_at' => $at, 'verified_at' => $at, 'allocation' => $allocation]);

        return [$order, $receipt];
    }

    private function refundFixture(Order $order, PaymentReceipt $receipt, User $admin, int $merchandise, int $shipping): void
    {
        Refund::create(['order_id' => $order->id, 'receipt_id' => $receipt->id, 'provider' => 'fixture', 'external_reference' => 'refund-'.$order->id, 'amount' => $merchandise + $shipping, 'merchandise_amount' => $merchandise, 'shipping_amount' => $shipping, 'refunded_at' => '2026-10-21 09:00:00', 'verified_by' => $admin->id, 'evidence_path' => 'evidence/test-fixture.txt', 'reason' => 'Test-only record']);
    }
}
