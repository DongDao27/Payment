<?php

use Illuminate\Contracts\Console\Kernel;

// This bootstrap is never loaded by public/index.php or application routes.
if (getenv('E2E_TESTING') !== '1' || getenv('APP_ENV') !== 'testing') {
    throw new RuntimeException('E2E requires an explicitly isolated testing process.');
}
$root = dirname(__DIR__, 3);
$storage = $root.'/.runtime/e2e-storage';
require_once $root.'/backend/vendor/autoload.php';
foreach (['app/public', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $directory) {
    if (! is_dir($storage.'/'.$directory)) {
        mkdir($storage.'/'.$directory, 0775, true);
    }
}
foreach (['APP_CONFIG_CACHE', 'APP_ROUTES_CACHE', 'APP_EVENTS_CACHE', 'APP_SERVICES_CACHE', 'APP_PACKAGES_CACHE'] as $key) {
    $_ENV[$key] = $_SERVER[$key] = $storage.'/framework/'.$key.'.php';
}
$app = require $root.'/backend/bootstrap/app.php';
if (preg_match('/^[A-Za-z]:/', $root)) {
    $app->addAbsoluteCachePathPrefix(substr($root, 0, 2));
}
$app->loadEnvironmentFrom('.env.testing');
$app->useStoragePath($storage);
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('testing') || config('database.default') !== 'mysql'
    || config('database.connections.mysql.database') !== 'thu_thanh_test') {
    throw new RuntimeException('E2E refuses any database except MySQL thu_thanh_test.');
}
config([
    'app.url' => 'http://localhost:18082', 'app.frontend_url' => 'http://localhost:3100',
    'session.cookie' => 'thu_thanh_e2e', 'session.domain' => null, 'session.secure' => false,
    'session.driver' => 'database', 'cache.default' => 'database',
    'cache.prefix' => 'thu_thanh_e2e_', 'sanctum.stateful' => ['localhost:3100', 'localhost:18082'],
    'filesystems.disks.public.root' => $storage.'/app/public',
    'filesystems.disks.public.url' => '/storage',
    'mail.default' => 'array',
    'payments.vnpay.deferred' => false, 'payments.vnpay.environment' => 'sandbox',
    'payments.vnpay.merchant' => 'E2EONLY1', 'payments.vnpay.secret' => 'e2e-provider-fixture-only',
    'payments.vnpay.payment_url' => 'https://e2e-provider.invalid/pay',
    'payments.vnpay.query_url' => 'https://e2e-provider.invalid/query',
    'payments.vnpay.return_url' => 'http://localhost:3100/payment/vnpay-return',
]);

return $app;
