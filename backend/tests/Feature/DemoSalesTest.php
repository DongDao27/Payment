<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentReceipt;
use App\Models\Product;
use App\Services\DemoSalesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_synthetic_sales_are_idempotent_and_preserve_money_stock_and_receipt_totals(): void
    {
        Product::factory()->count(3)->create();
        $seeder = app(DemoSalesSeeder::class);
        $result = $seeder->run(20, 20261023);
        $this->assertSame(20, $result['created']);
        $this->assertSame(20, PaymentReceipt::where('provider', 'demo')->count());
        foreach (Order::with('items', 'receipts')->get() as $order) {
            $this->assertSame($order->payable_total, $order->items->sum('line_total') + $order->shipping_fee);
            $this->assertSame($order->items_subtotal - $order->discount_total + $order->shipping_fee, $order->payable_total);
            $this->assertSame($order->payable_total, $order->receipts->sole()->received_amount);
        }
        $stock = Product::pluck('stock_on_hand', 'id')->all();
        $this->assertSame(20, $seeder->run(20, 20261023)['skipped']);
        $this->assertSame($stock, Product::pluck('stock_on_hand', 'id')->all());
        $this->assertSame(0, Product::sum('stock_reserved'));
    }

    public function test_command_rejects_production_and_invalid_counts_before_any_write(): void
    {
        $this->app->instance('env', 'production');
        $this->artisan('demo:seed-sales')->assertFailed();
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('users', 0);
        $this->app->instance('env', 'testing');
        $this->artisan('demo:seed-sales', ['--orders' => 'invalid'])->assertFailed();
        $this->artisan('demo:seed-sales', ['--orders' => '10001'])->assertFailed();
    }
}
