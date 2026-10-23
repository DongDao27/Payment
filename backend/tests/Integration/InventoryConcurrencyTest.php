<?php

namespace Tests\Integration;

use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\Support\OrderFixture;
use Tests\TestCase;

class InventoryConcurrencyTest extends TestCase
{
    use DatabaseTruncation, OrderFixture;

    public function test_two_independent_mysql_processes_cannot_reserve_the_same_last_item(): void
    {
        $product = Product::factory()->create(['stock_on_hand' => 1]);
        $orders = [$this->orderFor([$product]), $this->orderFor([$product])];
        $barrier = storage_path('framework/testing-races/'.Str::uuid());
        File::makeDirectory($barrier, recursive: true);
        $db = config('database.connections.mysql');
        $environment = [
            'APP_ENV' => 'testing', 'APP_KEY' => config('app.key'), 'DB_URL' => '',
            'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'thu_thanh_test',
            'DB_HOST' => $db['host'], 'DB_PORT' => (string) $db['port'],
            'DB_USERNAME' => $db['username'], 'DB_PASSWORD' => $db['password'],
        ];
        $processes = [];
        try {
            DB::beginTransaction();
            Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            foreach ($orders as $index => $order) {
                $process = new Process([PHP_BINARY, base_path('tests/Support/inventory-worker.php'), (string) $order->id, $barrier, (string) $index], base_path(), $environment);
                $process->setTimeout(20);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 10;
            while (! file_exists($barrier.'/ready-0') || ! file_exists($barrier.'/ready-1')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Independent MySQL workers did not reach the start barrier.');
                }
                usleep(10000);
            }
            file_put_contents($barrier.'/go', 'go');
            usleep(200000);
            DB::commit();
            $results = [];
            foreach ($processes as $process) {
                $this->assertSame(0, $process->wait(), $process->getErrorOutput());
                $results[] = trim($process->getOutput());
            }
            sort($results);
            $this->assertSame(['INSUFFICIENT_STOCK', 'RESERVED'], $results);
            $this->assertSame(1, $product->fresh()->stock_reserved);
            $this->assertSame(1, $product->fresh()->stock_on_hand);
            $this->assertDatabaseCount('stock_reservations', 1);
            $this->assertDatabaseCount('inventory_movements', 1);
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            foreach ($processes as $process) {
                $process->stop();
            }
            // Only this test's UUID directory beneath framework/testing-races.
            $resolved = realpath($barrier);
            $allowedRoot = realpath(storage_path('framework/testing-races'));
            if ($resolved !== false && $allowedRoot !== false && str_starts_with($resolved, $allowedRoot.DIRECTORY_SEPARATOR)) {
                File::deleteDirectory($resolved);
            }
        }
    }
}
