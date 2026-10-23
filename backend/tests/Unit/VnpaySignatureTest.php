<?php

namespace Tests\Unit;

use App\Models\PaymentAttempt;
use App\Payments\VnpayGateway;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VnpaySignatureTest extends TestCase
{
    public function test_pay_canonicalization_matches_an_independent_python_hmac_fixture(): void
    {
        config(['payments.vnpay.secret' => 'test-only-secret']);
        $signature = app(VnpayGateway::class)->sign(['vnp_TxnRef' => 'fixed-ref', 'vnp_OrderInfo' => 'Thu Thanh test + space', 'vnp_Amount' => '3500000', 'vnp_TmnCode' => 'TESTMERCH']);
        $this->assertSame('8e367682e9c450acc84636f862fe0a82203abe91518b1a8f162af6d59c0a9d170ae77723652647c511e8caa98dac8489fd67ec96cd7118a35203531f2d6e1fb4', $signature);
    }

    public function test_query_response_requires_pipe_signature_reference_and_transaction_status(): void
    {
        config(['payments.vnpay.environment' => 'sandbox', 'payments.vnpay.merchant' => 'TESTMERCH', 'payments.vnpay.secret' => 'test-only-secret', 'payments.vnpay.payment_url' => 'https://sandbox.example.test/pay', 'payments.vnpay.return_url' => 'https://store.example.test/return', 'payments.vnpay.query_url' => 'https://sandbox.example.test/query']);
        $attempt = new PaymentAttempt(['provider_reference' => 'fixed-ref', 'requested_amount' => 35000, 'expires_at' => now()->addMinutes(5)]);
        $attempt->created_at = now();
        $response = ['vnp_ResponseId' => 'query-response', 'vnp_Command' => 'querydr', 'vnp_ResponseCode' => '00', 'vnp_Message' => 'Fixture result', 'vnp_TmnCode' => 'TESTMERCH', 'vnp_TxnRef' => 'fixed-ref', 'vnp_Amount' => '3500000', 'vnp_BankCode' => 'NCB', 'vnp_PayDate' => '20261021120000', 'vnp_TransactionNo' => '123456', 'vnp_TransactionType' => '01', 'vnp_TransactionStatus' => '00', 'vnp_OrderInfo' => 'Test', 'vnp_PromotionCode' => '', 'vnp_PromotionAmount' => ''];
        $response['vnp_SecureHash'] = hash_hmac('sha512', implode('|', array_values($response)), 'test-only-secret');
        Http::preventStrayRequests();
        $altered = [...$response, 'vnp_TransactionStatus' => '02'];
        Http::fake(['https://sandbox.example.test/query' => Http::sequence()->push($response)->push($altered)]);
        $verified = app(VnpayGateway::class)->query($attempt);
        $this->assertSame('succeeded', $verified?->state);
        $this->assertSame(35000, $verified?->amount);
        $this->assertSame('2026-10-21T05:00:00+00:00', $verified?->paidAt?->toIso8601String());
        Http::assertSent(function ($request) {
            $data = $request->data();
            $fields = ['vnp_RequestId', 'vnp_Version', 'vnp_Command', 'vnp_TmnCode', 'vnp_TxnRef', 'vnp_TransactionDate', 'vnp_CreateDate', 'vnp_IpAddr', 'vnp_OrderInfo'];
            $expected = hash_hmac('sha512', implode('|', array_map(fn ($field) => $data[$field], $fields)), 'test-only-secret');

            return hash_equals($expected, $data['vnp_SecureHash']);
        });
        // Second response alters status without recomputing its signature.
        $this->assertNull(app(VnpayGateway::class)->query($attempt));
    }
}
