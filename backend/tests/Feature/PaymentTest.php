<?php

namespace Tests\Feature;

use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\User;
use App\Payments\VnpayGateway;
use App\Services\InventoryService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Support\OrderFixture;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use OrderFixture, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        // Adapter fixtures exercise provider behavior separately from the deferred owner policy.
        config(['payments.vnpay.deferred' => false]);
        config(['payments.vnpay.merchant' => 'TESTMERCH', 'payments.vnpay.secret' => 'test-only-secret', 'payments.vnpay.payment_url' => 'https://sandbox.example.test/pay', 'payments.vnpay.return_url' => 'http://localhost:8000/api/v1/payments/vnpay/return', 'payments.vnpay.query_url' => 'https://sandbox.example.test/query']);
    }

    public function test_verified_success_consumes_once_and_duplicate_ipn_is_acknowledged(): void
    {
        [$order, $product, $attempt] = $this->attempt();
        $payload = $this->payload($attempt);
        $this->ipn($payload)->assertExactJson(['RspCode' => '00', 'Message' => 'Confirm Success']);
        $this->ipn($payload)->assertExactJson(['RspCode' => '02', 'Message' => 'Order already confirmed']);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('succeeded', $attempt->fresh()->status);
        $this->assertSame(0, $product->fresh()->stock_reserved);
        $this->assertSame(0, $product->fresh()->stock_on_hand);
        $this->assertDatabaseCount('payment_receipts', 1);
        $this->assertDatabaseCount('payment_events', 1);
        $this->assertDatabaseCount('inventory_movements', 2);
    }

    public function test_invalid_signature_merchant_reference_and_amount_have_provider_ack_and_no_sale(): void
    {
        [$order, $product, $attempt] = $this->attempt();
        $this->ipn([...$this->payload($attempt), 'vnp_SecureHash' => str_repeat('a', 128)])->assertJsonPath('RspCode', '97');
        $this->ipn($this->payload($attempt, ['vnp_TmnCode' => 'OTHER']))->assertJsonPath('RspCode', '97');
        $this->ipn($this->payload($attempt, ['vnp_TxnRef' => 'not-found']))->assertJsonPath('RspCode', '01');
        $this->ipn($this->payload($attempt, ['vnp_Amount' => '100']))->assertJsonPath('RspCode', '04');
        $this->assertSame('reconciliation_required', $order->fresh()->payment_status);
        $this->assertSame(1, $product->fresh()->stock_reserved);
        $this->assertDatabaseHas('payment_receipts', ['allocation' => 'mismatch', 'received_amount' => 1]);
        $this->assertDatabaseCount('reconciliation_cases', 1);
    }

    public function test_return_never_marks_paid_and_failure_after_success_never_downgrades(): void
    {
        [$order, $product, $attempt] = $this->attempt();
        $this->getJson('/api/v1/payments/vnpay/return?'.http_build_query($this->payload($attempt)))->assertOk();
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertDatabaseCount('payment_receipts', 0);
        $this->ipn($this->payload($attempt))->assertJsonPath('RspCode', '00');
        $this->ipn($this->payload($attempt, ['vnp_ResponseCode' => '24', 'vnp_TransactionStatus' => '02']))->assertJsonPath('RspCode', '00');
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('succeeded', $attempt->fresh()->status);
        $this->assertSame(0, $product->fresh()->stock_on_hand);
    }

    public function test_second_success_is_unallocated_duplicate_and_never_consumes_twice(): void
    {
        [$order, $product, $attempt] = $this->attempt();
        $this->ipn($this->payload($attempt))->assertJsonPath('RspCode', '00');
        $another = PaymentAttempt::create(['order_id' => $order->id, 'provider' => 'vnpay', 'provider_reference' => 'another-ref', 'requested_amount' => 35000, 'status' => 'pending', 'expires_at' => $order->expires_at]);
        $this->ipn($this->payload($another, ['vnp_TransactionNo' => '222222']))->assertJsonPath('RspCode', '00');
        $this->assertDatabaseHas('payment_receipts', ['allocation' => 'duplicate']);
        $this->assertDatabaseCount('reconciliation_cases', 1);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(0, $product->fresh()->stock_on_hand);
        $this->assertTrue(app(PaymentService::class)->status($order->fresh())['reconciliation_required']);
    }

    public function test_late_receipt_after_release_never_reopens_order(): void
    {
        [$order, $product, $attempt] = $this->attempt();
        app(InventoryService::class)->release($order);
        $order->update(['lifecycle' => 'expired']);
        $this->ipn($this->payload($attempt))->assertJsonPath('RspCode', '00');
        $this->assertSame('expired', $order->fresh()->lifecycle);
        $this->assertDatabaseHas('payment_receipts', ['allocation' => 'late']);
        $this->assertSame(1, $product->fresh()->stock_on_hand);
        $this->assertSame(0, $product->fresh()->stock_reserved);
    }

    public function test_pending_attempt_is_reused_and_amount_is_converted_once(): void
    {
        [$order, $product, $attempt] = $this->attempt();
        $this->actingAs($order->user);
        $this->postJson('/api/v1/orders/'.$order->order_code.'/payment-attempts', [], ['Idempotency-Key' => 'second-payment-key'])->assertOk()->assertJsonPath('data.id', $attempt->id);
        $this->assertDatabaseCount('payment_attempts', 1);
        $url = app(VnpayGateway::class)->paymentUrl($attempt, '127.0.0.1');
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('3500000', $query['vnp_Amount']);
        $this->assertTrue(hash_equals(app(VnpayGateway::class)->sign($query), $query['vnp_SecureHash']));
    }

    public function test_missing_config_and_pickup_qr_are_explicitly_unavailable(): void
    {
        $product = Product::factory()->create();
        $order = $this->orderFor([$product]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->postJson('/api/v1/admin/orders/'.$order->order_code.'/payment-attempts', [], ['Idempotency-Key' => 'qr-payment-key'])->assertStatus(503)->assertJsonPath('error.code', 'QR_CAPABILITY_UNAVAILABLE');
        $order->update(['fulfillment_method' => 'delivery', 'address' => ['address_line' => 'Fixture', 'locality' => 'Fixture', 'province' => 'Fixture']]);
        $this->actingAs($order->user);
        config(['payments.vnpay.secret' => null]);
        $this->postJson('/api/v1/orders/'.$order->order_code.'/payment-attempts', [], ['Idempotency-Key' => 'payment-test-key'])->assertStatus(503)->assertJsonPath('error.code', 'GATEWAY_NOT_CONFIGURED');
        $this->assertDatabaseCount('payment_attempts', 0);
    }

    public function test_query_timeout_is_unknown_and_no_http_request_is_made_inside_transaction(): void
    {
        [$order, $product, $attempt] = $this->attempt();
        Http::fake(['https://sandbox.example.test/query' => function () {
            $this->assertSame(1, DB::transactionLevel()); // RefreshDatabase test wrapper only.

            return Http::failedConnection();
        }]);
        $this->assertNull(app(VnpayGateway::class)->query($attempt));
        $this->assertSame('pending', $attempt->fresh()->status);
        $this->assertSame(1, $product->fresh()->stock_reserved);
    }

    public function test_success_with_invalid_provider_time_requires_reconciliation(): void
    {
        [$order, $product, $attempt] = $this->attempt();
        $this->ipn($this->payload($attempt, ['vnp_PayDate' => 'invalid']))->assertJsonPath('RspCode', '00');
        $this->assertSame('reconciliation_required', $order->fresh()->payment_status);
        $this->ipn($this->payload($attempt, ['vnp_ResponseCode' => '24', 'vnp_TransactionStatus' => '02']))->assertJsonPath('RspCode', '00');
        $this->assertSame('reconciliation_required', $order->fresh()->payment_status);
        $this->assertSame(1, $product->fresh()->stock_reserved);
    }

    public function test_customer_pays_pickup_order_online(): void
    {
        $product = Product::factory()->create(['stock_on_hand' => 1]);
        $order = $this->orderFor([$product]);
        app(InventoryService::class)->reserve($order);
        $this->actingAs($order->user);
        $response = $this->postJson('/api/v1/orders/'.$order->order_code.'/payment-attempts', [], ['Idempotency-Key' => 'pickup-payment-key'])->assertOk()->assertJsonPath('data.status', 'pending');
        $this->assertStringStartsWith('https://sandbox.example.test/pay?', $response->json('data.payment_url'));
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_return_with_bad_signature_or_unknown_reference_never_queries_provider(): void
    {
        [$order, $product, $attempt] = $this->attempt();
        Http::fake();
        $this->returned([...$this->payload($attempt), 'vnp_SecureHash' => str_repeat('a', 128)])->assertStatus(422)->assertJsonPath('error.code', 'INVALID_PAYMENT_RETURN');
        $this->returned($this->payload($attempt, ['vnp_TxnRef' => 'not-found']))->assertNotFound()->assertJsonPath('error.code', 'PAYMENT_NOT_FOUND');
        Http::assertNothingSent();
        $this->assertNull($attempt->fresh()->query_requested_at);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_signed_success_return_allocates_only_through_verified_query_once(): void
    {
        [$order, $product, $attempt] = $this->attempt();
        Http::fake(['https://sandbox.example.test/query' => $this->queryResponse($attempt)]);
        $payload = $this->payload($attempt);
        $this->returned($payload)->assertOk()->assertJsonPath('data.order_code', $order->order_code)->assertJsonPath('data.payment_status', 'paid');
        $this->returned($payload)->assertOk()->assertJsonPath('data.payment_status', 'paid');
        Http::assertSentCount(1);
        $this->assertDatabaseCount('payment_receipts', 1);
        $this->assertDatabaseHas('payment_events', ['source' => 'query', 'disposition' => 'accepted']);
        $this->assertDatabaseMissing('payment_events', ['source' => 'return']);
        $this->assertSame(0, $product->fresh()->stock_on_hand);
        $this->assertSame(0, $product->fresh()->stock_reserved);
    }

    public function test_unconfirmed_success_return_stays_pending_without_reconciliation_or_requery(): void
    {
        [$order, $product, $attempt] = $this->attempt();
        Http::fake(['https://sandbox.example.test/query' => Http::failedConnection()]);
        $this->returned($this->payload($attempt))->assertOk()->assertJsonPath('data.payment_status', 'pending');
        $this->returned($this->payload($attempt))->assertOk();
        Http::assertSentCount(1);
        $this->assertSame('pending', $attempt->fresh()->status);
        $this->assertDatabaseCount('payment_receipts', 0);
        $this->assertDatabaseCount('reconciliation_cases', 0);
        $this->assertSame(1, $product->fresh()->stock_reserved);
    }

    public function test_signed_failed_return_frees_order_for_a_new_attempt(): void
    {
        [$order, $product, $attempt] = $this->attempt();
        Http::fake();
        $failed = $this->payload($attempt, ['vnp_ResponseCode' => '24', 'vnp_TransactionStatus' => '02', 'vnp_TransactionNo' => '0']);
        $this->returned($failed)->assertOk()->assertJsonPath('data.result', 'failed')->assertJsonPath('data.payment_status', 'unpaid');
        $this->returned($failed)->assertOk()->assertJsonPath('data.payment_status', 'unpaid');
        Http::assertNothingSent();
        $this->assertSame('failed', $attempt->fresh()->status);
        $this->assertDatabaseCount('payment_events', 1);
        $this->assertTrue(app(PaymentService::class)->status($order->fresh())['can_retry']);
        $this->actingAs($order->user);
        $retry = $this->postJson('/api/v1/orders/'.$order->order_code.'/payment-attempts', [], ['Idempotency-Key' => 'retry-payment-key'])->assertOk();
        $this->assertNotSame($attempt->id, $retry->json('data.id'));
        $this->assertNotSame($attempt->provider_reference, PaymentAttempt::findOrFail($retry->json('data.id'))->provider_reference);
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame(1, $product->fresh()->stock_reserved);
    }

    private function attempt(): array
    {
        $product = Product::factory()->create(['stock_on_hand' => 1]);
        $order = $this->orderFor([$product]);
        $order->update(['fulfillment_method' => 'delivery', 'address' => ['address_line' => 'Fixture', 'locality' => 'Fixture', 'province' => 'Fixture']]);
        app(InventoryService::class)->reserve($order);
        $attempt = app(PaymentService::class)->create($order, $order->user_id, 'fixture-payment-key');

        return [$order, $product, $attempt];
    }

    private function payload(PaymentAttempt $attempt, array $overrides = []): array
    {
        $payload = array_merge(['vnp_TmnCode' => 'TESTMERCH', 'vnp_TxnRef' => $attempt->provider_reference, 'vnp_Amount' => (string) ($attempt->requested_amount * 100), 'vnp_TransactionNo' => '111111', 'vnp_ResponseCode' => '00', 'vnp_TransactionStatus' => '00', 'vnp_PayDate' => now('Asia/Ho_Chi_Minh')->format('YmdHis')], $overrides);
        $payload['vnp_SecureHash'] = app(VnpayGateway::class)->sign($payload);

        return $payload;
    }

    private function queryResponse(PaymentAttempt $attempt): array
    {
        $response = ['vnp_ResponseId' => 'query-response', 'vnp_Command' => 'querydr', 'vnp_ResponseCode' => '00', 'vnp_Message' => 'Fixture result', 'vnp_TmnCode' => 'TESTMERCH', 'vnp_TxnRef' => $attempt->provider_reference, 'vnp_Amount' => (string) ($attempt->requested_amount * 100), 'vnp_BankCode' => 'NCB', 'vnp_PayDate' => now('Asia/Ho_Chi_Minh')->format('YmdHis'), 'vnp_TransactionNo' => '111111', 'vnp_TransactionType' => '01', 'vnp_TransactionStatus' => '00', 'vnp_OrderInfo' => 'Fixture', 'vnp_PromotionCode' => '', 'vnp_PromotionAmount' => ''];
        $response['vnp_SecureHash'] = hash_hmac('sha512', implode('|', array_values($response)), 'test-only-secret');

        return $response;
    }

    private function ipn(array $payload)
    {
        return $this->getJson('/api/v1/payments/vnpay/ipn?'.http_build_query($payload));
    }

    private function returned(array $payload)
    {
        return $this->getJson('/api/v1/payments/vnpay/return?'.http_build_query($payload));
    }
}
