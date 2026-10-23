<?php

namespace Tests\Feature;

use App\Exceptions\BusinessException;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\OrderFixture;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use OrderFixture, RefreshDatabase;

    public function test_reserve_consume_and_release_are_idempotent_and_ledger_balances(): void
    {
        $product = Product::factory()->create(['stock_on_hand' => 2]);
        $order = $this->orderFor([$product]);
        $inventory = app(InventoryService::class);
        $inventory->reserve($order);
        $inventory->reserve($order);
        $this->assertSame(1, $product->fresh()->stock_reserved);
        $inventory->consume($order);
        $inventory->consume($order);
        $inventory->release($order);
        $this->assertSame(1, $product->fresh()->stock_on_hand);
        $this->assertSame(0, $product->fresh()->stock_reserved);
        $this->assertDatabaseCount('inventory_movements', 2);
        $this->assertSame(-1, (int) InventoryMovement::sum('delta_on_hand'));
        $this->assertSame(0, (int) InventoryMovement::sum('delta_reserved'));
        $second = $this->orderFor([$product]);
        $inventory->reserve($second);
        $inventory->release($second);
        $inventory->release($second);
        $this->assertSame(1, $product->fresh()->stock_on_hand);
        $this->assertSame(0, $product->fresh()->stock_reserved);
        $this->expectException(BusinessException::class);
        $inventory->consume($second);
    }

    public function test_second_item_failure_rolls_back_the_entire_reservation(): void
    {
        $first = Product::factory()->create(['stock_on_hand' => 1]);
        $second = Product::factory()->create(['stock_on_hand' => 0]);
        try {
            app(InventoryService::class)->reserve($this->orderFor([$first, $second]));
            $this->fail('Insufficient stock was accepted.');
        } catch (BusinessException $exception) {
            $this->assertSame('INSUFFICIENT_STOCK', $exception->errorCode);
        }
        $this->assertSame(0, $first->fresh()->stock_reserved);
        $this->assertDatabaseCount('stock_reservations', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_adjustment_requires_reason_and_preserves_reserved_stock_with_replay_safety(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $product = Product::factory()->create(['stock_on_hand' => 5, 'stock_reserved' => 4]);
        $url = '/api/v1/admin/inventory/'.$product->id.'/adjustments';
        $body = ['delta' => 3, 'reason' => 'Verified delivery', 'request_key' => 'stock-001'];
        $this->postJson($url, $body)->assertCreated()->assertJsonPath('data.stock_on_hand', 8);
        $this->postJson($url, $body)->assertCreated()->assertJsonPath('data.stock_on_hand', 8);
        $this->postJson($url, [...$body, 'delta' => 4])->assertConflict()->assertJsonPath('error.code', 'IDEMPOTENCY_CONFLICT');
        $this->postJson($url, [...$body, 'request_key' => 'stock-002', 'delta' => -5])->assertConflict();
        $this->postJson($url, ['delta' => 1, 'request_key' => 'stock-003'])->assertUnprocessable();
        $this->getJson('/api/v1/admin/inventory?category_id='.$product->category_id)->assertOk()->assertJsonPath('data.0.available_quantity', 4);
        $this->assertDatabaseCount('inventory_movements', 1);
    }
}
