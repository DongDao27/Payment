<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\PaymentReceipt;
use App\Models\Product;
use App\Models\Quote;
use App\Models\StockReservation;
use App\Models\User;
use App\Support\ProductPricing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Random\Engine\Mt19937;
use Random\Randomizer;
use RuntimeException;

class DemoSalesSeeder
{
    public function run(int $count, int $seed): array
    {
        $database = DB::connection()->getDatabaseName();
        $localDemoConsole = app()->environment('demo') && config('app.env') === 'local' && $database === 'thu_thanh_demo';
        if ((! app()->environment(['local', 'testing']) && ! $localDemoConsole) || ! in_array($database, ['thu_thanh_demo', 'thu_thanh_test'], true)) {
            throw new RuntimeException('Synthetic sales are allowed only in local thu_thanh_demo or isolated thu_thanh_test.');
        }
        if ($count < 1 || $count > 10000 || $seed < 1) {
            throw new RuntimeException('Choose 1–10000 orders and a positive seed.');
        }
        $products = Product::where('is_active', true)->whereHas('category', fn ($q) => $q->where('is_active', true))->orderBy('id')->get();
        if ($products->isEmpty()) {
            throw new RuntimeException('Import the catalog into the isolated demo database first.');
        }
        $password = Hash::make(Str::random(48));
        $admin = User::firstOrCreate(['email' => 'admin-demo@example.test'], ['name' => 'Quản trị demo', 'password' => $password]);
        // Existing real admin is never changed. A new isolated admin receives no public/default password.
        if ($admin->wasRecentlyCreated && ! User::where('role', 'admin')->exists()) {
            $admin->forceFill(['role' => 'admin'])->save();
        }
        $inventory = app(InventoryService::class);
        foreach ($products as $index => $product) {
            $inventory->adjust($product, 50000, 'Synthetic demo stock', 'demo:'.$seed.':stock:'.$product->id, $admin->id);
            if ($index % 3 === 0 && $product->sale_price === null) {
                $product->update(['sale_price' => ProductPricing::salePrice($product->list_price, [5, 10, 15, 20][$index % 4]), 'catalog_version' => $product->catalog_version + 1]);
            }
        }
        $customers = collect(range(1, 20))->map(fn ($i) => User::firstOrCreate(['email' => 'demo-'.$seed.'-'.$i.'@example.test'], ['name' => 'Khách demo '.$i, 'password' => $password]));
        $random = new Randomizer(new Mt19937($seed));
        $created = $skipped = 0;
        for ($i = 1; $i <= $count; $i++) {
            $code = 'DEMO-'.$seed.'-'.str_pad((string) $i, 6, '0', STR_PAD_LEFT);
            $customer = $customers[$random->getInt(0, 19)];
            $date = now()->subDays($random->getInt(0, 89))->subMinutes($random->getInt(2, 1400));
            $delivery = $random->getInt(0, 1) === 1;
            $selected = $random->pickArrayKeys($products->all(), min($products->count(), $random->getInt(1, 3)));
            $lines = [];
            foreach ($selected as $key) {
                $product = $products[$key];
                $quantity = $random->getInt(1, 4);
                $unit = $product->sale_price ?? $product->list_price;
                $lines[] = ['product_id' => $product->id, 'sku' => $product->sku, 'name' => $product->name, 'list_unit_price' => $product->list_price, 'charged_unit_price' => $unit, 'quantity' => $quantity, 'discount_line_total' => ($product->list_price - $unit) * $quantity, 'line_total' => $unit * $quantity];
            }
            if (Order::where('order_code', $code)->exists()) {
                $skipped++;

                continue;
            }
            DB::transaction(function () use ($code, $customer, $date, $delivery, $lines, $inventory) {
                $cart = Cart::firstOrCreate(['user_id' => $customer->id]);
                $subtotal = array_sum(array_map(fn ($line) => $line['list_unit_price'] * $line['quantity'], $lines));
                $discount = array_sum(array_column($lines, 'discount_line_total'));
                $totals = ['items_subtotal' => $subtotal, 'discount_total' => $discount, 'shipping_fee' => $delivery ? 25000 : 0, 'payable_total' => $subtotal - $discount + ($delivery ? 25000 : 0)];
                $quote = Quote::create(['user_id' => $customer->id, 'cart_id' => $cart->id, 'cart_fingerprint' => hash('sha256', $code), 'payload' => ['items' => $lines, 'totals' => $totals], ...$totals, 'pricing_version' => 1, 'expires_at' => $date->copy()->addMinutes(5), 'consumed_at' => $date]);
                $order = Order::create(['order_code' => $code, 'user_id' => $customer->id, 'quote_id' => $quote->id, 'fulfillment_method' => $delivery ? 'delivery' : 'pickup', 'recipient_name' => $customer->name, 'recipient_phone' => '+84900000000', 'address' => $delivery ? ['address_line' => 'Địa chỉ demo', 'locality' => 'Hòa Khánh', 'province' => 'Đà Nẵng'] : null, ...$totals, 'expires_at' => $date->copy()->addSeconds($delivery ? 300 : 86400)]);
                foreach ($lines as $line) {
                    $order->items()->create($line);
                }
                // Exercise the real stock transitions while the temporary hold is valid,
                // then date the isolated historical fixture consistently after consumption.
                $historicalExpiry = $order->expires_at;
                $order->update(['expires_at' => now()->addDay()]);
                $inventory->reserve($order);
                $inventory->consume($order);
                StockReservation::whereIn('order_item_id', $order->items()->select('id'))->update(['expires_at' => $historicalExpiry]);
                $paidAt = $date->copy()->addMinute();
                $attempt = PaymentAttempt::create(['order_id' => $order->id, 'provider' => 'demo', 'provider_reference' => $code, 'requested_amount' => $order->payable_total, 'status' => 'succeeded', 'expires_at' => $order->expires_at, 'provider_transaction_id' => $code, 'verified_at' => $paidAt]);
                PaymentReceipt::create(['provider' => 'demo', 'provider_transaction_id' => $code, 'order_id' => $order->id, 'attempt_id' => $attempt->id, 'received_amount' => $order->payable_total, 'provider_paid_at' => $paidAt, 'verified_at' => $paidAt, 'allocation' => 'accepted']);
                $order->forceFill(['payment_status' => 'paid', 'paid_at' => $paidAt, 'lifecycle' => 'completed', 'fulfillment_status' => 'fulfilled', 'completed_at' => $paidAt->copy()->addMinute(), 'created_at' => $date, 'expires_at' => $historicalExpiry, 'version' => 2])->save();
            }, 3);
            $created++;
        }
        StorefrontSnapshot::invalidate();

        return ['synthetic' => true, 'database' => DB::connection()->getDatabaseName(), 'seed' => $seed, 'created' => $created, 'skipped' => $skipped, 'total_for_seed' => Order::where('order_code', 'like', 'DEMO-'.$seed.'-%')->count()];
    }
}
