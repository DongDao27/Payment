<?php

$deliveryFee = env('STORE_DELIVERY_FEE_VND', 25000);

return [
    'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', env('TRUSTED_PROXIES', ''))))),
    'delivery_hold_seconds' => 300,
    'pickup_hold_seconds' => 86400,
    'quote_ttl_seconds' => (int) env('STORE_QUOTE_TTL_SECONDS', 300),
    'max_quantity' => 99,
    'max_order_vnd' => 1000000000,
    'delivery_fee_vnd' => $deliveryFee === null || $deliveryFee === '' ? 25000 : $deliveryFee,
    'after_sales_policy' => 'no_returns_no_refunds',
    'revenue_policy' => env('STORE_REVENUE_POLICY', 'approved_v1'),
    // Owner policy D30: a legacy env toggle cannot enable refund writes.
    'refund_records_enabled' => false,
];
