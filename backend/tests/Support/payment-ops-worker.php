<?php

use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Payments\VerifiedPayment;
use App\Services\PaymentService;
use App\Services\ReconciliationService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'mysql' || config('database.connections.mysql.database') !== 'thu_thanh_test') {
    throw new RuntimeException('Operational race requires isolated MySQL thu_thanh_test.');
}
[$script, $action, $orderId, $reference, $paidAt, $barrier, $worker] = $argv;
file_put_contents($barrier.'/ready-'.$worker, 'ready');
$deadline = microtime(true) + 15;
while (! file_exists($barrier.'/go')) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Operational race timed out.');
    }
    usleep(10000);
}
if ($action === 'expire') {
    $order = app(ReconciliationService::class)->close(Order::findOrFail($orderId));
    echo 'CLOSED_'.$order->lifecycle;
} else {
    $attempt = PaymentAttempt::where('provider_reference', $reference)->firstOrFail();
    // This test-only worker exercises allocation after the verification boundary.
    // Production never constructs payment evidence from an unauthenticated request.
    $result = new VerifiedPayment($reference, 'succeeded', $attempt->requested_amount, '999111', CarbonImmutable::parse($paidAt), ['fixture' => true], hash('sha256', 'fixture-'.$reference));
    echo 'PAYMENT_'.app(PaymentService::class)->apply($result, 'ipn');
}
