<?php

namespace App\Payments;

use Carbon\CarbonImmutable;

final readonly class VerifiedPayment
{
    public function __construct(
        public string $reference,
        public string $state,
        public int $amount,
        public ?string $transactionId,
        public ?CarbonImmutable $paidAt,
        public array $safePayload,
        public string $fingerprint,
    ) {}
}
