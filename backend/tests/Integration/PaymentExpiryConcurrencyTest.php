<?php

namespace Tests\Integration;

use App\Models\InventoryMovement;
use App\Models\PaymentAttempt;
use App\Models\PaymentReceipt;
use App\Models\Product;
use App\Models\StockReservation;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\Support\OrderFixture;
use Tests\Support\RunsConcurrentProcesses;
use Tests\TestCase;

class PaymentExpiryConcurrencyTest extends TestCase
{
    use DatabaseTruncation, OrderFixture, RunsConcurrentProcesses;

    public function test_duplicate_verified_notifications_across_processes_allocate_only_once(): void
    {
        [$order, $product, $attempt] = $this->fixture();
        $args = ['pay', (string) $order->id, $attempt->provider_reference, now()->toISOString()];
        $outputs = $this->runOperationalRace($order, [$args, $args]);
        sort($outputs);
        $this->assertSame(['PAYMENT_00', 'PAYMENT_02'], $outputs);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(0, $product->fresh()->stock_on_hand);
        $this->assertSame(0, $product->fresh()->stock_reserved);
        $this->assertDatabaseCount('payment_receipts', 1);
        $this->assertDatabaseCount('inventory_movements', 2);
    }

    public function test_expiry_and_verified_money_race_cannot_both_release_and_consume(): void
    {
        [$order, $product, $attempt] = $this->fixture();
        $order->update(['expires_at' => now()->subSecond()]);
        $attempt->created_at = now()->subSeconds(10);
        $attempt->save();
        $when = now()->subSeconds(2)->toISOString();
        $outputs = $this->runOperationalRace($order, [['expire', (string) $order->id, $attempt->provider_reference, $when], ['pay', (string) $order->id, $attempt->provider_reference, $when]]);
        $this->assertContains('PAYMENT_00', $outputs);
        $reservation = StockReservation::firstOrFail();
        $this->assertContains($reservation->status, ['consumed', 'released']);
        $receipt = PaymentReceipt::firstOrFail();
        if ($reservation->status === 'consumed') {
            $this->assertSame('accepted', $receipt->allocation);
            $this->assertSame('paid', $order->fresh()->payment_status);
            $this->assertSame(0, $product->fresh()->stock_on_hand);
        } else {
            $this->assertSame('late', $receipt->allocation);
            $this->assertSame('expired', $order->fresh()->lifecycle);
            $this->assertSame(1, $product->fresh()->stock_on_hand);
        }
        $this->assertSame(0, $product->fresh()->stock_reserved);
        $this->assertSame(1, InventoryMovement::whereIn('kind', ['sale', 'release'])->count());
        $this->assertDatabaseCount('inventory_movements', 2);
    }

    private function fixture(): array
    {
        $product = Product::factory()->create(['stock_on_hand' => 1]);
        $order = $this->orderFor([$product]);
        app(InventoryService::class)->reserve($order);
        $attempt = PaymentAttempt::create(['order_id' => $order->id, 'provider' => 'vnpay', 'provider_reference' => 'race-payment-'.$order->id, 'requested_amount' => $order->payable_total, 'status' => 'failed', 'expires_at' => $order->expires_at]);

        return [$order, $product, $attempt];
    }
}
