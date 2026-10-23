<?php

// Creates a separate local database/storage; reads development catalog only.
use App\Services\DemoSalesSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__);
require $root.'/backend/vendor/autoload.php';
$backend = $root.'/backend';
if (is_file($backend.'/bootstrap/cache/config.php')) {
    throw new RuntimeException('Cached config detected. Use an uncached local environment for demo preparation.');
}
$source = Dotenv\Dotenv::parse(file_get_contents($backend.'/.env'));
if (($source['DB_DATABASE'] ?? '') !== 'thu_thanh') {
    throw new RuntimeException('Expected the existing local thu_thanh catalog as a read-only source.');
}
$envFile = $backend.'/.env.demo';
if (! is_file($envFile)) {
    $admin = parse_ini_file($root.'/.runtime/mysql-admin.cnf', true)['client'];
    $pdo = new PDO('mysql:host='.$admin['host'].';port='.$admin['port'].';charset=utf8mb4', $admin['user'], $admin['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    if ($pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='thu_thanh_demo'")->fetch()) {
        throw new RuntimeException('Demo database exists without its private env. Refusing to replace credentials or data.');
    }
    $password = bin2hex(random_bytes(24));
    $pdo->exec('CREATE DATABASE `thu_thanh_demo` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec("CREATE USER 'thu_thanh_demo'@'127.0.0.1' IDENTIFIED BY ".$pdo->quote($password));
    $pdo->exec("GRANT ALL PRIVILEGES ON `thu_thanh_demo`.* TO 'thu_thanh_demo'@'127.0.0.1'");
    $env = file_get_contents($backend.'/.env');
    $settings = ['APP_NAME' => '"Thu Thanh Demo"', 'APP_ENV' => 'local', 'APP_DEBUG' => 'false', 'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)), 'APP_URL' => 'http://localhost:18081', 'DB_DATABASE' => 'thu_thanh_demo', 'DB_USERNAME' => 'thu_thanh_demo', 'DB_PASSWORD' => $password, 'SESSION_COOKIE' => 'thu_thanh_demo', 'CACHE_PREFIX' => 'thu_thanh_demo_', 'CACHE_STORE' => 'database', 'QUEUE_CONNECTION' => 'database', 'MAIL_MAILER' => 'array', 'VNPAY_DEFERRED' => 'true', 'ADMIN_PASSWORD' => '', 'ADMIN_EMAIL' => '', 'ADMIN_PHONE' => ''];
    foreach ($settings as $key => $value) {
        $env = preg_match('/^'.preg_quote($key, '/').'=/m', $env) ? preg_replace('/^'.preg_quote($key, '/').'=.*$/m', $key.'='.$value, $env) : $env."\n$key=$value\n";
    }
    file_put_contents($envFile, $env);
}
Dotenv\Dotenv::createImmutable($backend, '.env.demo')->load();
$storage = $root.'/.runtime/demo-storage';
$_ENV['LARAVEL_STORAGE_PATH'] = $_SERVER['LARAVEL_STORAGE_PATH'] = $storage;
foreach (['app/public', 'app/private', 'framework/views', 'framework/cache/data', 'framework/sessions', 'logs'] as $directory) {
    if (! is_dir($storage.'/'.$directory)) {
        mkdir($storage.'/'.$directory, 0777, true);
    }
}
$app = require $backend.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (DB::connection()->getDatabaseName() !== 'thu_thanh_demo' || ! app()->environment('local')) {
    throw new RuntimeException('Only the separate local demo database may be prepared.');
}
echo "Preparing isolated thu_thanh_demo; development catalog remains read-only.\n";
Artisan::call('migrate', ['--force' => true]);
$dsn = 'mysql:host='.$source['DB_HOST'].';port='.$source['DB_PORT'].';dbname=thu_thanh;charset=utf8mb4';
$sourcePdo = new PDO($dsn, $source['DB_USERNAME'], $source['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
if (DB::table('products')->count() === 0) {
    DB::transaction(function () use ($sourcePdo) {
        foreach (['categories', 'products', 'product_images'] as $table) {
            $rows = $sourcePdo->query('SELECT * FROM '.$table)->fetchAll(PDO::FETCH_ASSOC);
            if ($table === 'products') {
                foreach ($rows as &$row) {
                    $row['stock_reserved'] = 0;
                } unset($row);
            }
            foreach (array_chunk($rows, 100) as $chunk) {
                DB::table($table)->insert($chunk);
            }
        }
        foreach (DB::table('products')->get() as $product) {
            DB::table('inventory_movements')->insert(['product_id' => $product->id, 'kind' => 'opening', 'delta_on_hand' => $product->stock_on_hand, 'delta_reserved' => 0, 'resulting_on_hand' => $product->stock_on_hand, 'resulting_reserved' => 0, 'reason' => 'Isolated demo catalog opening', 'business_key' => 'product:'.$product->id.':opening', 'created_at' => now(), 'updated_at' => now()]);
        }
    });
}
// Isolated copies prevent demo image deletion from deleting development originals.
$sourcePublic = realpath($backend.'/storage/app/public');
foreach (DB::table('product_images')->get() as $image) {
    $path = $image->path;
    $original = realpath($sourcePublic.'/'.$path);
    if (! $original || ! str_starts_with($original, $sourcePublic.DIRECTORY_SEPARATOR)) {
        continue;
    }
    $target = $storage.'/app/public/'.$path;
    if (! is_file($target)) {
        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }
        copy($original, $target);
    }
}
$result = app(DemoSalesSeeder::class)->run(1000, 20261023);
file_put_contents($root.'/.runtime/demo-dataset.json', json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo json_encode($result, JSON_THROW_ON_ERROR).PHP_EOL;
echo "Demo admin: use php artisan admin:account --env=demo and admin:password --env=demo to choose your own local password. No default password exists.\n";
