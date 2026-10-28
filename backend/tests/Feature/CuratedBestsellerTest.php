<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CuratedBestsellerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_select_on_create_edit_and_clear_without_fabricating_sales(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $response = $this->postJson('/api/v1/admin/products', [
            'name' => 'Sách được chọn', 'sku' => 'CURATED-1',
            'category_id' => Category::factory()->create()->id,
            'list_price' => 100000, 'opening_stock' => 10, 'is_bestseller' => true,
        ])->assertCreated()->assertJsonPath('data.is_bestseller', true);
        $id = $response->json('data.id');
        $this->getJson('/api/v1/storefront')->assertOk()->assertJsonPath('data.bestseller.0.id', $id)->assertJsonPath('data.bestseller.0.sold_count', 0);
        $this->getJson('/api/v1/products?section=bestseller')->assertOk()->assertJsonCount(1, 'data');
        $this->patchJson('/api/v1/admin/products/'.$id, ['version' => 1, 'is_bestseller' => false])->assertOk();
        $this->getJson('/api/v1/storefront')->assertOk()->assertJsonCount(0, 'data.bestseller');
        foreach (['orders', 'payment_receipts'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertDatabaseHas('products', ['id' => $id, 'stock_on_hand' => 10]);
        $this->actingAs(User::factory()->create())->patchJson('/api/v1/admin/products/'.$id, ['version' => 2, 'is_bestseller' => true])->assertForbidden();
    }

    public function test_hidden_products_and_categories_stay_hidden_and_landing_has_at_most_seven(): void
    {
        Product::factory()->count(9)->create(['is_bestseller' => true]);
        Product::factory()->create(['is_bestseller' => true, 'is_active' => false]);
        Product::factory()->create(['is_bestseller' => true, 'category_id' => Category::factory()->create(['is_active' => false])]);
        $this->getJson('/api/v1/storefront')->assertOk()->assertJsonCount(7, 'data.bestseller');
        $this->getJson('/api/v1/products?section=bestseller')->assertOk()->assertJsonCount(9, 'data');
    }
}
