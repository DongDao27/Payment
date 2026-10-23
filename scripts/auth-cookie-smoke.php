<?php

// Real HTTP, real session database, actual CSRF middleware. Isolated test DB only.
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

$backend = dirname(__DIR__).'/backend';
require $backend.'/vendor/autoload.php';
if (file_exists($backend.'/bootstrap/cache/config.php')) {
    throw new RuntimeException('Clear cached config before running isolated HTTP smoke.');
}
if (is_file($backend.'/.env.testing')) {
    Dotenv\Dotenv::createImmutable($backend, '.env.testing')->load();
}
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
$app = require $backend.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'mysql' || config('database.connections.mysql.database') !== 'thu_thanh_test') {
    throw new RuntimeException('HTTP smoke requires isolated MySQL thu_thanh_test.');
}
$password = bin2hex(random_bytes(16));
$email = 'smoke-'.bin2hex(random_bytes(6)).'@example.test';
$user = User::create(['name' => 'HTTP smoke fixture', 'email' => $email, 'password' => $password]);
$port = 18080;
$environment = array_merge($_ENV, [
    'APP_ENV' => 'testing', 'APP_DEBUG' => 'false', 'SESSION_DRIVER' => 'database', 'CACHE_STORE' => 'array',
    'APP_URL' => "http://localhost:$port",
]);
$server = new Process([PHP_BINARY, '-S', "localhost:$port", $backend.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'], $backend.'/public', $environment);
$checks = 0;
$assert = function (bool $valid, string $label) use (&$checks): void {
    if (! $valid) {
        throw new RuntimeException('FAIL: '.$label);
    }
    $checks++;
    echo 'PASS: '.$label.PHP_EOL;
};
try {
    $server->start();
    $jar = new CookieJar;
    $client = new Client(['base_uri' => "http://localhost:$port", 'cookies' => $jar, 'http_errors' => false, 'timeout' => 5, 'headers' => ['Accept' => 'application/json', 'Origin' => 'http://localhost:3000']]);
    $ready = false;
    for ($retry = 0; $retry < 40; $retry++) {
        if (! $server->isRunning()) {
            throw new RuntimeException('HTTP test server failed to start.');
        }
        try {
            $ready = $client->get('/up')->getStatusCode() === 200;
        } catch (Throwable) {
            usleep(100000);
        }
        if ($ready) {
            break;
        }
    }
    $assert($ready, 'HTTP server boots');
    $assert($client->get('/sanctum/csrf-cookie')->getStatusCode() === 204, 'CSRF cookie issued');
    $credentials = ['identifier' => $email, 'password' => $password];
    $assert($client->post('/api/v1/auth/login', ['json' => $credentials])->getStatusCode() === 419, 'Missing CSRF rejected over HTTP');
    $assert($client->post('/api/v1/auth/login', ['json' => $credentials, 'headers' => ['X-XSRF-TOKEN' => 'invalid']])->getStatusCode() === 419, 'Invalid CSRF rejected');
    $oldSessionIds = DB::table('sessions')->pluck('id')->all();
    $csrf = fn () => urldecode($jar->getCookieByName('XSRF-TOKEN')->getValue());
    $assert($client->post('/api/v1/auth/login', ['json' => $credentials, 'headers' => ['X-XSRF-TOKEN' => $csrf()]])->getStatusCode() === 200, 'Login with cookie and CSRF succeeds');
    $sessionId = DB::table('sessions')->where('user_id', $user->id)->value('id');
    $assert($sessionId !== null && ! in_array($sessionId, $oldSessionIds, true), 'Login regenerates server session');
    $me = $client->get('/api/v1/auth/me');
    $assert($me->getStatusCode() === 200 && json_decode((string) $me->getBody(), true)['data']['id'] === $user->id, 'Cookie authenticates me');
    $replayJar = new CookieJar(false, $jar->toArray());
    $assert($client->post('/api/v1/auth/logout')->getStatusCode() === 419, 'Logout also requires CSRF');
    $assert($client->post('/api/v1/auth/logout', ['headers' => ['X-XSRF-TOKEN' => $csrf()]])->getStatusCode() === 204, 'Logout succeeds');
    $assert(! DB::table('sessions')->where('id', $sessionId)->exists(), 'Logout destroys authenticated server session');
    $assert($client->get('/api/v1/auth/me', ['cookies' => $replayJar])->getStatusCode() === 401, 'Old authenticated cookie cannot be replayed');
    echo "HTTP cookie/CSRF smoke: $checks checks passed; isolated MySQL.\n";
} finally {
    $server->stop();
    DB::table('sessions')->where('user_id', $user->id)->delete();
    $user->delete();
}
