<?php

// Isolated browser QA only. Never loads the development database credentials.
use App\Models\User;
use App\Services\CatalogImportService;
use Database\Seeders\CategorySeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

$root = dirname(__DIR__);
$backend = $root.'/backend';
require $backend.'/vendor/autoload.php';
if (is_file($backend.'/bootstrap/cache/config.php')) {
    throw new RuntimeException('Clear cached config before isolated browser QA.');
}
Dotenv\Dotenv::createImmutable($backend, '.env.testing')->load();
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
$testStorage = $root.'/.runtime/frontend-browser-storage';
foreach (['app/public', 'app/private', 'framework/views', 'framework/cache/data', 'framework/sessions', 'logs'] as $directory) {
    if (! is_dir($testStorage.'/'.$directory)) {
        mkdir($testStorage.'/'.$directory, 0777, true);
    }
}
$_ENV['LARAVEL_STORAGE_PATH'] = $_SERVER['LARAVEL_STORAGE_PATH'] = $testStorage;
$app = require $backend.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'mysql' || config('database.connections.mysql.database') !== 'thu_thanh_test') {
    throw new RuntimeException('Browser QA requires isolated MySQL thu_thanh_test.');
}
if (User::where('role', 'admin')->exists()) {
    throw new RuntimeException('An admin already exists in the test DB. No credentials changed.');
}
$manifest = $argv[1] ?? '';
if (! is_file($manifest)) {
    throw new RuntimeException('Provide the staged owner workbook manifest.');
}
(new CategorySeeder)->run();
app(CatalogImportService::class)->run($manifest, true);
// Existing test SKUs are intentionally skipped by the importer. Restore only
// their identical fixture images into the isolated filesystem, never dev storage.
$staged = json_decode(file_get_contents($manifest), true, flags: JSON_THROW_ON_ERROR);
foreach ($staged['products'] as $product) {
    if (! preg_match('/^images\/TT-[A-Z]+-[0-9]+\.(jpg|png)$/D', $product['image'])) {
        throw new RuntimeException('Invalid fixture image path.');
    }
    $bytes = file_get_contents(dirname($manifest).'/'.$product['image']);
    if (! hash_equals($product['image_sha256'], hash('sha256', $bytes))) {
        throw new RuntimeException('Fixture image hash mismatch.');
    }
    Storage::disk('public')->put('products/imports/'.$staged['sha256'].'/basename-'.basename($product['image']), $bytes);
}
$suffix = bin2hex(random_bytes(6));
$adminPassword = bin2hex(random_bytes(16));
$adminEmail = 'browser-admin-'.$suffix.'@example.test';
$admin = new User(['name' => 'Browser QA admin', 'email' => $adminEmail, 'password' => $adminPassword]);
$admin->role = 'admin';
$admin->save();
$file = $root.'/.runtime/frontend-browser-fixture.json';
$stop = $root.'/.runtime/frontend-browser-stop';
if (is_file($stop)) {
    unlink($stop);
}
file_put_contents($file, json_encode(['admin_email' => $adminEmail, 'admin_password' => $adminPassword, 'customer_email' => 'browser-customer-'.$suffix.'@example.test', 'customer_password' => bin2hex(random_bytes(16))], JSON_THROW_ON_ERROR));
$server = new Process([PHP_BINARY, '-S', 'localhost:18080', $root.'/scripts/frontend-test-router.php'], $backend.'/public', array_merge($_ENV, [
    'APP_ENV' => 'testing', 'APP_DEBUG' => 'false', 'APP_URL' => 'http://localhost:18080',
    'SESSION_DRIVER' => 'database', 'SESSION_COOKIE' => 'thu_thanh_browser_test', 'CACHE_STORE' => 'array',
]));
try {
    $server->start();
    echo "Browser QA: isolated MySQL thu_thanh_test, localhost:18080; credentials saved only under ignored .runtime.\n";
    while (! is_file($stop) && $server->isRunning()) {
        usleep(200000);
    }
} finally {
    $server->stop();
    if (is_file($file)) {
        unlink($file);
    }
    if (is_file($stop)) {
        unlink($stop);
    }
    // Test orders/stock remain inspectable in thu_thanh_test; no dev records are touched.
    // Retain the fixture operator too: stock/order audit rows can reference it.
    // A later PHPUnit RefreshDatabase run resets only the guarded test database.
}
