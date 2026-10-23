<?php

namespace App\Payments;

use App\Models\PaymentAttempt;

interface PaymentGateway
{
    public function assertAvailable(bool $inStoreQr = false): void;

    public function paymentUrl(PaymentAttempt $attempt, string $ip): string;

    public function verifyCallback(array $payload): ?VerifiedPayment;

    public function query(PaymentAttempt $attempt): ?VerifiedPayment;
}
