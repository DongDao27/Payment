<?php

namespace Tests\Feature;

use Tests\TestCase;

class VnpaySandboxCheckTest extends TestCase
{
    public function test_missing_credentials_or_deferred_configuration_are_not_reported_ready(): void
    {
        config(['payments.vnpay.merchant' => null, 'payments.vnpay.secret' => null, 'payments.vnpay.deferred' => true]);
        $this->artisan('payments:check-sandbox')->expectsOutputToContain('[THIẾU/SAI] Đã điền mã merchant')->assertFailed();
    }

    public function test_complete_sandbox_configuration_is_checked_without_printing_credentials(): void
    {
        config([
            'app.frontend_url' => 'http://localhost:3000',
            'payments.vnpay.environment' => 'sandbox',
            'payments.vnpay.merchant' => 'private-test-merchant',
            'payments.vnpay.secret' => 'private-test-signing-key',
            'payments.vnpay.deferred' => false,
            'payments.vnpay.payment_url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
            'payments.vnpay.query_url' => 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction',
            'payments.vnpay.return_url' => 'http://localhost:3000/payment/vnpay-return',
        ]);
        $this->artisan('payments:check-sandbox')->doesntExpectOutputToContain('private-test-merchant')
            ->doesntExpectOutputToContain('private-test-signing-key')->assertSuccessful();
        config(['payments.vnpay.environment' => 'production']);
        $this->artisan('payments:check-sandbox')->assertFailed();
        config(['payments.vnpay.environment' => 'sandbox', 'payments.vnpay.return_url' => 'https://different.example.test/payment/vnpay-return']);
        $this->artisan('payments:check-sandbox')->assertFailed();
    }
}
