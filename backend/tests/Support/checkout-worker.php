<?php

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'mysql' || config('database.connections.mysql.database') !== 'thu_thanh_test') {
    throw new RuntimeException('Checkout worker requires isolated MySQL thu_thanh_test.');
}
[$script, $userId, $quoteId, $key, $barrierPath, $workerId] = $argv;
file_put_contents($barrierPath.'/ready-'.$workerId, 'ready');
$deadline = microtime(true) + 15;
while (! file_exists($barrierPath.'/go')) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Checkout race barrier timed out.');
    }
    usleep(10000);
}
try {
    $result = app(CheckoutService::class)->create(User::findOrFail($userId), $quoteId, $key);
    echo $result['replayed'] ? 'REPLAYED' : 'CREATED';
} catch (BusinessException $exception) {
    if ($exception->errorCode !== 'INSUFFICIENT_STOCK') {
        throw $exception;
    }
    echo 'INSUFFICIENT_STOCK';
}
