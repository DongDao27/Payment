<?php

use App\Jobs\DeleteProductImageFile;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

$backend = dirname(__DIR__).'/backend';
require $backend.'/vendor/autoload.php';
if (is_file($backend.'/bootstrap/cache/config.php')) {
    throw new RuntimeException('Clear config cache before isolated operational smoke.');
}
if (is_file($backend.'/.env.testing')) {
    Dotenv\Dotenv::createImmutable($backend, '.env.testing')->load();
}
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
$app = require $backend.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'mysql' || config('database.connections.mysql.database') !== 'thu_thanh_test') {
    throw new RuntimeException('Ops smoke requires isolated MySQL thu_thanh_test.');
}
$db = config('database.connections.mysql');
$environment = [
    'APP_ENV' => 'testing', 'APP_KEY' => config('app.key'), 'APP_DEBUG' => 'false', 'DB_URL' => '',
    'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'thu_thanh_test', 'DB_HOST' => $db['host'], 'DB_PORT' => (string) $db['port'],
    'DB_USERNAME' => $db['username'], 'DB_PASSWORD' => $db['password'], 'QUEUE_CONNECTION' => 'database', 'CACHE_STORE' => 'database',
];
$path = 'ops-smoke/'.bin2hex(random_bytes(12)).'.txt';
Storage::disk('local')->put($path, 'Isolated queue smoke fixture');
$jobId = Queue::connection('database')->push(new DeleteProductImageFile('local', $path));
try {
    foreach ([['queue:work', 'database', '--once', '--tries=1', '--timeout=30'], ['orders:expire', '--limit=10'], ['payments:reconcile', '--limit=10'], ['schedule:run']] as $arguments) {
        $process = new Process([PHP_BINARY, $backend.'/artisan', ...$arguments], $backend, $environment);
        $process->setTimeout(60);
        $process->mustRun();
        echo 'PASS: '.implode(' ', $arguments).PHP_EOL;
    }
    if (Storage::disk('local')->exists($path) || DB::table('jobs')->where('id', $jobId)->exists()) {
        throw new RuntimeException('Worker did not consume the idempotent cleanup job.');
    }
    echo "PASS: real database queue consumed job and removed fixture.\nOperational smoke passed on isolated MySQL.\n";
} finally {
    Storage::disk('local')->delete($path);
}
