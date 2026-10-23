<?php

return [
    'provider' => 'vnpay',
    'vnpay' => [
        'deferred' => (bool) env('VNPAY_DEFERRED', true),
        'environment' => env('VNPAY_ENV', 'sandbox'),
        'merchant' => env('VNPAY_TMN_CODE'),
        'secret' => env('VNPAY_HASH_SECRET'),
        'payment_url' => env('VNPAY_PAYMENT_URL'),
        'query_url' => env('VNPAY_QUERY_URL'),
        'return_url' => env('VNPAY_RETURN_URL'),
        'query_ip' => env('VNPAY_QUERY_IP', '127.0.0.1'),
        // PAY redirect is not a merchant-specific in-store QR adapter.
        'qr_capability_verified' => env('VNPAY_QR_CAPABILITY_VERIFIED', false),
    ],
];
