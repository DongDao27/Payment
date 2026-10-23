<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\OrderFixture;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use OrderFixture, RefreshDatabase;

    public function test_dashboard_is_admin_only_and_empty_chart_has_seven_zero_days(): void
    {
        $this->getJson('/api/v1/admin/dashboard')->assertUnauthorized();
        $this->actingAs(User::factory()->create())->getJson('/api/v1/admin/dashboard')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $response = $this->getJson('/api/v1/admin/dashboard')->assertOk()->assertJsonCount(7, 'data.daily_orders')->assertJsonCount(0, 'data.recent_orders');
        $this->assertSame(0, array_sum(array_column($response->json('data.daily_orders'), 'count')));
        $response->assertJsonMissingPath('data.revenue')->assertJsonPath('data.counts.orders_today', 0);
    }

    public function test_counts_use_vietnam_day_boundary_and_real_active_catalog(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-23 02:00:00', 'UTC'));
        $visible = Category::factory()->create(['is_active' => true]);
        $hidden = Category::factory()->create(['is_active' => false]);
        $product = Product::factory()->create(['category_id' => $visible->id, 'is_active' => true]);
        Product::factory()->create(['category_id' => $visible->id, 'is_active' => false]);
        Product::factory()->create(['category_id' => $hidden->id, 'is_active' => true]);
        foreach ([['2026-10-22 16:59:59', 'open'], ['2026-10-22 17:00:00', 'open'], ['2026-10-23 02:00:00', 'cancelled'], ['2026-10-23 17:00:00', 'expired'], ['2026-10-16 16:59:59', 'expired']] as [$created, $state]) {
            $order = $this->orderFor([$product]);
            $order->lifecycle = $state;
            $order->created_at = $created;
            $order->save();
        }
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $response = $this->getJson('/api/v1/admin/dashboard')->assertOk()
            ->assertJsonPath('data.timezone', 'Asia/Ho_Chi_Minh')->assertJsonPath('data.counts.active_products', 1)
            ->assertJsonPath('data.counts.active_categories', 1)->assertJsonPath('data.counts.orders_today', 2)
            ->assertJsonPath('data.counts.awaiting_processing', 2)->assertJsonPath('data.daily_orders.0.date', '2026-10-17')
            ->assertJsonPath('data.daily_orders.5.count', 1)->assertJsonPath('data.daily_orders.6.count', 2)
            ->assertJsonCount(5, 'data.recent_orders')->assertJsonCount(1, 'data.recent_orders.0.items');
        $this->assertSame(3, array_sum(array_column($response->json('data.daily_orders'), 'count')));
        $this->travelBack();
    }
}
