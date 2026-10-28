<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\OrderFixture;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use OrderFixture, RefreshDatabase;

    public function test_public_catalog_hides_inactive_products_and_categories(): void
    {
        $active = Product::factory()->create(['name' => 'Visible', 'is_hot' => true]);
        $hidden = Product::factory()->create(['is_active' => false]);
        $hiddenCategory = Product::factory()->create(['category_id' => Category::factory()->create(['is_active' => false])]);
        $this->getJson('/api/v1/products')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $active->id);
        $this->getJson('/api/v1/products/'.$hidden->id)->assertNotFound();
        $this->getJson('/api/v1/products/'.$hiddenCategory->id)->assertNotFound();
        $this->getJson('/api/v1/home')->assertOk()->assertJsonPath('data.hot.0.id', $active->id);
        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_search_price_sort_and_pagination_are_stable_and_whitelisted(): void
    {
        $first = Product::factory()->create(['name' => 'Book one', 'list_price' => 40000, 'sale_price' => 30000]);
        $second = Product::factory()->create(['name' => 'Book two', 'list_price' => 30000]);
        Product::factory()->create(['name' => 'Other', 'list_price' => 100]);
        $this->getJson('/api/v1/products?q=Book&sort=price_asc&per_page=1')->assertOk()
            ->assertJsonPath('data.0.id', $first->id)->assertJsonPath('meta.pagination.total', 2);
        $this->getJson('/api/v1/products?q=Book&sort=price_asc&per_page=1&page=2')->assertOk()->assertJsonPath('data.0.id', $second->id);
        $this->getJson('/api/v1/products?sort=password')->assertUnprocessable();
        $this->getJson('/api/v1/products?per_page=101')->assertUnprocessable();
        $this->getJson('/api/v1/products?q=%25')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_search_supports_vietnamese_without_accents_and_escapes_literal_wildcards(): void
    {
        $book = Product::factory()->create(['name' => 'Sách tiếng Việt']);
        $literal = Product::factory()->create(['name' => 'Bộ %_! đặc biệt']);
        foreach (['sach', 'SÁCH', 'tieng viet'] as $term) {
            $this->getJson('/api/v1/products?'.http_build_query(['q' => $term, 'per_page' => 6]))
                ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $book->id);
        }
        foreach (['%', '_', '!', '%_!'] as $term) {
            $this->getJson('/api/v1/products?'.http_build_query(['q' => $term]))
                ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $literal->id);
        }
    }

    public function test_admin_creates_product_and_opening_ledger_then_updates_with_version(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $response = $this->postJson('/api/v1/admin/products', $this->input())->assertCreated();
        $id = $response->json('data.id');
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $id, 'kind' => 'opening', 'delta_on_hand' => 7]);
        $this->patchJson('/api/v1/admin/products/'.$id, ['version' => 1, 'name' => 'Updated', 'is_active' => false])->assertOk()->assertJsonPath('data.catalog_version', 2);
        $this->patchJson('/api/v1/admin/products/'.$id, ['version' => 1, 'name' => 'Stale'])->assertConflict()->assertJsonPath('error.code', 'VERSION_CONFLICT');
        $this->patchJson('/api/v1/admin/products/'.$id, ['version' => 2, 'stock_reserved' => 0])->assertUnprocessable();
        $this->patchJson('/api/v1/admin/products/'.$id, ['version' => 2, 'stock_on_hand' => 99])->assertUnprocessable();
        $this->getJson('/api/v1/admin/products?is_active=0')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_grade_price_and_category_rules_are_enforced_including_partial_updates(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $data = $this->input();
        $this->postJson('/api/v1/admin/products', [...$data, 'grade' => 1])->assertUnprocessable();
        $this->postJson('/api/v1/admin/products', [...$data, 'sale_price' => 999999])->assertUnprocessable();
        $id = $this->postJson('/api/v1/admin/products', [...$data, 'sale_price' => 30000])->assertCreated()->json('data.id');
        $this->patchJson('/api/v1/admin/products/'.$id, ['version' => 1, 'list_price' => 20000])->assertUnprocessable();
        $this->assertDatabaseHas('products', ['id' => $id, 'list_price' => 35000, 'catalog_version' => 1]);
    }

    public function test_customer_cannot_mutate_catalog_and_public_reads_do_not_expose_private_fields(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create());
        $this->postJson('/api/v1/admin/products', $this->input())->assertForbidden();
        $this->patchJson('/api/v1/admin/products/'.$product->id, ['version' => 1, 'name' => 'Hacked'])->assertForbidden();
        $this->getJson('/api/v1/products/'.$product->id)->assertOk()->assertJsonMissingPath('data.stock_reserved')->assertJsonMissingPath('data.created_at');
    }

    public function test_category_changes_invalidate_affected_product_versions(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->patchJson('/api/v1/admin/categories/'.$product->category_id, ['is_active' => false])->assertOk();
        $this->assertSame(2, $product->fresh()->catalog_version);
        $this->getJson('/api/v1/products/'.$product->id)->assertNotFound();
    }

    private function input(): array
    {
        return ['category_id' => Category::factory()->create()->id, 'sku' => 'TEST-BOOK', 'slug' => 'test-book', 'name' => 'Test Book', 'list_price' => 35000, 'opening_stock' => 7];
    }

    public function test_admin_can_create_without_slug_and_rename_keeps_generated_slug(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $input = $this->input();
        unset($input['slug']);
        $input['name'] = 'Sách tiếng Việt mới';
        $input['discount_percent'] = 15;
        $first = $this->postJson('/api/v1/admin/products', $input)->assertCreated()
            ->assertJsonPath('data.unit_price', 29750)->json('data');
        $this->assertMatchesRegularExpression('/^sach-tieng-viet-moi-[a-z0-9]+$/', $first['slug']);
        $second = $this->postJson('/api/v1/admin/products', [...$input, 'sku' => 'TEST-BOOK-2'])->assertCreated()->json('data');
        $this->assertNotSame($first['slug'], $second['slug']);
        $this->patchJson('/api/v1/admin/products/'.$first['id'], ['name' => 'Tên mới', 'version' => 1])
            ->assertOk()->assertJsonPath('data.slug', $first['slug']);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $first['id'], 'kind' => 'opening', 'delta_on_hand' => 7]);
        $this->postJson('/api/v1/admin/products', $input)->assertUnprocessable()->assertJsonPath('error.fields.sku.0', 'Mã SKU này đã được sử dụng. Vui lòng nhập mã khác.');
        $this->assertDatabaseCount('products', 2);
        $long = $this->postJson('/api/v1/admin/products', [...$input, 'sku' => 'TEST-LONG', 'name' => str_repeat('a', 255)])->assertCreated()->json('data');
        $this->assertLessThanOrEqual(255, strlen($long['slug']));
        $this->patchJson('/api/v1/admin/products/'.$first['id'], ['version' => 2, 'is_active' => false])->assertOk();
        $this->getJson('/api/v1/admin/products/'.$first['id'])->assertOk()->assertJsonPath('data.is_active', false);
        $this->actingAs(User::factory()->create());
        $this->getJson('/api/v1/admin/products/'.$first['id'])->assertForbidden();
    }

    public function test_hiding_or_repricing_product_preserves_existing_order_snapshots(): void
    {
        $product = Product::factory()->create(['name' => 'Original title', 'list_price' => 35000]);
        $order = $this->orderFor([$product]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->patchJson('/api/v1/admin/products/'.$product->id, ['version' => 1, 'name' => 'New title', 'list_price' => 50000, 'is_active' => false])->assertOk();
        $item = $order->items()->firstOrFail();
        $this->assertSame('Original title', $item->name);
        $this->assertSame(35000, $item->charged_unit_price);
        $this->assertSame(35000, $order->fresh()->payable_total);
    }
}
