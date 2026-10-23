<?php

namespace Tests\Integration;

use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class CheckoutConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    public function test_two_customers_checking_out_last_item_produce_one_complete_order(): void
    {
        $product = Product::factory()->create(['stock_on_hand' => 1]);
        $results = $this->race($product, [$this->input($product), $this->input($product)]);
        $this->assertSame(['CREATED', 'INSUFFICIENT_STOCK'], $results);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseCount('stock_reservations', 1);
        $this->assertDatabaseCount('inventory_movements', 1);
        $this->assertDatabaseCount('cart_items', 1);
        $this->assertSame(1, $product->fresh()->stock_reserved);
    }

    public function test_double_submit_same_key_across_processes_returns_same_single_order(): void
    {
        $product = Product::factory()->create(['stock_on_hand' => 1]);
        $input = $this->input($product);
        $results = $this->race($product, [$input, $input]);
        $this->assertSame(['CREATED', 'REPLAYED'], $results);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('idempotency_keys', 1);
        $this->assertDatabaseCount('stock_reservations', 1);
        $this->assertDatabaseCount('inventory_movements', 1);
        $this->assertDatabaseCount('cart_items', 0);
    }

    private function input(Product $product): array
    {
        $user = User::factory()->create();
        app(CartService::class)->add($user, $product->id, 1);
        $quote = app(QuoteService::class)->create($user, ['fulfillment_method' => 'pickup', 'recipient_name' => 'Race fixture', 'recipient_phone' => '+84912345678']);

        return [(string) $user->id, $quote->id, 'race-checkout-key'];
    }

    private function race(Product $product, array $inputs): array
    {
        $barrier = storage_path('framework/testing-races/'.Str::uuid());
        File::makeDirectory($barrier, recursive: true);
        $db = config('database.connections.mysql');
        $environment = ['APP_ENV' => 'testing', 'APP_KEY' => config('app.key'), 'DB_URL' => '', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'thu_thanh_test', 'DB_HOST' => $db['host'], 'DB_PORT' => (string) $db['port'], 'DB_USERNAME' => $db['username'], 'DB_PASSWORD' => $db['password']];
        $processes = [];
        try {
            DB::beginTransaction();
            Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            foreach ($inputs as $index => $input) {
                $process = new Process([PHP_BINARY, base_path('tests/Support/checkout-worker.php'), ...$input, $barrier, (string) $index], base_path(), $environment);
                $process->setTimeout(20);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 10;
            while (! file_exists($barrier.'/ready-0') || ! file_exists($barrier.'/ready-1')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Checkout workers did not reach the start barrier.');
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

            return $results;
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
