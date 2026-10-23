<?php

namespace Tests\Support;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

trait RunsConcurrentProcesses
{
    protected function runOperationalRace(Order $order, array $arguments): array
    {
        $barrier = storage_path('framework/testing-races/'.Str::uuid());
        File::makeDirectory($barrier, recursive: true);
        $db = config('database.connections.mysql');
        $environment = ['APP_ENV' => 'testing', 'APP_KEY' => config('app.key'), 'DB_URL' => '', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'thu_thanh_test', 'DB_HOST' => $db['host'], 'DB_PORT' => (string) $db['port'], 'DB_USERNAME' => $db['username'], 'DB_PASSWORD' => $db['password']];
        $processes = [];
        try {
            DB::beginTransaction();
            Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            foreach ($arguments as $index => $args) {
                $process = new Process([PHP_BINARY, base_path('tests/Support/payment-ops-worker.php'), ...$args, $barrier, (string) $index], base_path(), $environment);
                $process->setTimeout(20);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 10;
            while (! file_exists($barrier.'/ready-0') || ! file_exists($barrier.'/ready-1')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Operational workers did not reach start barrier.');
                }
                usleep(10000);
            }
            file_put_contents($barrier.'/go', 'go');
            usleep(200000);
            DB::commit();
            $outputs = [];
            foreach ($processes as $process) {
                $this->assertSame(0, $process->wait(), $process->getErrorOutput());
                $outputs[] = trim($process->getOutput());
            }

            return $outputs;
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            foreach ($processes as $process) {
                $process->stop();
            }
            $resolved = realpath($barrier);
            $allowedRoot = realpath(storage_path('framework/testing-races'));
            if ($resolved !== false && $allowedRoot !== false && str_starts_with($resolved, $allowedRoot.DIRECTORY_SEPARATOR)) {
                File::deleteDirectory($resolved);
            }
        }
    }
}
