<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\StorefrontSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DiscountStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_percent_is_calculated_on_server_with_integer_rounding_and_version(): void
    {
        $product = Product::factory()->create(['list_price' => 10001]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $url = '/api/v1/admin/products/'.$product->id;
        $this->patchJson($url, ['discount_percent' => 5, 'version' => 1])->assertOk()->assertJsonPath('data.sale_price', 9501)->assertJsonPath('data.discount_percent', 5);
        $this->patchJson($url, ['discount_percent' => 20, 'version' => 2])->assertOk()->assertJsonPath('data.sale_price', 8001);
        $this->patchJson($url, ['discount_percent' => 0, 'version' => 3])->assertOk()->assertJsonPath('data.sale_price', null)->assertJsonPath('data.discount_percent', 0);
        $this->patchJson($url, ['discount_percent' => 100, 'version' => 4])->assertOk()->assertJsonPath('data.unit_price', 0)->assertJsonPath('data.discount_percent', 100);
        $this->patchJson($url, ['discount_percent' => 1, 'version' => 1])->assertConflict();
    }

    public function test_percent_validation_rejects_ambiguous_out_of_range_and_customer_input(): void
    {
        $product = Product::factory()->create();
        $url = '/api/v1/admin/products/'.$product->id;
        $this->actingAs(User::factory()->create())->patchJson($url, ['discount_percent' => 10, 'version' => 1])->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach ([-1, 101, 5.5, 'abc'] as $percent) {
            $this->patchJson($url, ['discount_percent' => $percent, 'version' => 1])->assertUnprocessable();
        }
        $this->patchJson($url, ['discount_percent' => 5, 'sale_price' => 100, 'version' => 1])->assertUnprocessable();
        $this->assertSame(1, $product->fresh()->catalog_version);
    }

    public function test_public_snapshot_warm_hit_has_no_database_queries_cookies_or_private_data(): void
    {
        Product::factory()->create(['list_price' => 10000, 'sale_price' => 7500]);
        $first = $this->getJson('/api/v1/storefront')->assertOk()->assertHeader('Cache-Control', 'max-age=0, public, s-maxage=20, stale-while-revalidate=20')->assertHeaderMissing('Set-Cookie');
        DB::enableQueryLog();
        DB::flushQueryLog();
        $second = $this->getJson('/api/v1/storefront')->assertOk()->assertHeaderMissing('Set-Cookie');
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertSame($first->json('data'), $second->json('data'));
        $this->assertNotSame($first->json('meta.request_id'), $second->json('meta.request_id'));
        $second->assertJsonPath('data.new.0.discount_percent', 25)->assertJsonMissingPath('data.users')->assertJsonMissingPath('data.orders');
    }

    public function test_committed_product_mutation_invalidates_snapshot_and_private_home_stays_private(): void
    {
        $product = Product::factory()->create(['list_price' => 10000]);
        $this->getJson('/api/v1/storefront')->assertJsonPath('data.new.0.unit_price', 10000);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->patchJson('/api/v1/admin/products/'.$product->id, ['discount_percent' => 25, 'version' => 1])->assertOk();
        $this->getJson('/api/v1/storefront')->assertJsonPath('data.new.0.unit_price', 7500);
        $this->getJson('/api/v1/home')->assertHeader('Cache-Control', 'no-store, private');
        $this->deleteJson('/api/v1/admin/products/'.$product->id, ['version' => 2])->assertNoContent();
        $this->getJson('/api/v1/storefront')->assertJsonCount(0, 'data.new');
    }

    public function test_public_cache_never_includes_customer_recommendation_preferences(): void
    {
        $mine = Product::factory()->create();
        $recent = Product::factory()->create();
        $customer = User::factory()->create();
        $cart = Cart::create(['user_id' => $customer->id]);
        $cart->items()->create(['product_id' => $mine->id, 'quantity' => 1]);
        $guest = $this->getJson('/api/v1/storefront')->assertOk()->json('data');
        $this->actingAs($customer)->getJson('/api/v1/home')->assertJsonPath('data.recommended.0.id', $mine->id);
        $this->assertSame($guest, $this->getJson('/api/v1/storefront')->assertOk()->json('data'));
    }

    public function test_recommendations_hydrate_at_most_twelve_products_per_category(): void
    {
        $categories = Category::factory()->count(3)->create();
        foreach ($categories as $category) {
            Product::factory()->count(50)->create(['category_id' => $category->id]);
        }
        $retrieved = 0;
        Product::retrieved(function () use (&$retrieved) {
            $retrieved++;
        });
        StorefrontSnapshot::invalidate();
        $this->getJson('/api/v1/storefront')->assertOk()->assertJsonCount(12, 'data.recommended');
        $this->assertLessThanOrEqual(48, $retrieved);
    }

    public function test_rolled_back_price_change_does_not_invalidate_or_publish_uncommitted_prices(): void
    {
        $product = Product::factory()->create(['list_price' => 10000]);
        $this->getJson('/api/v1/storefront')->assertJsonPath('data.new.0.unit_price', 10000);
        $revision = Cache::get('storefront:revision');
        DB::beginTransaction();
        $product->update(['sale_price' => 9000]);
        $this->assertSame($revision, Cache::get('storefront:revision'));
        $this->assertSame(10000, app(StorefrontSnapshot::class)->data()['new'][0]['unit_price']);
        DB::rollBack();
        $this->getJson('/api/v1/storefront')->assertJsonPath('data.new.0.unit_price', 10000);
    }
}
