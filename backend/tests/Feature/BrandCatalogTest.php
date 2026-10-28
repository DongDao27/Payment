<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_brands_with_visible_products_are_shown_and_filter_is_literal(): void
    {
        $first = Product::factory()->create(['description' => "Nhóm: Bút\nNXB / Thương hiệu: Thiên Long"]);
        Product::factory()->create(['description' => 'NXB / Thương hiệu: Hidden', 'is_active' => false]);
        Product::factory()->create(['description' => 'NXB / Thương hiệu: Không thương hiệu']);
        $this->getJson('/api/v1/storefront')->assertOk()->assertJsonCount(2, 'data.brands');
        $this->getJson('/api/v1/products?'.http_build_query(['brand' => 'Thiên Long']))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $first->id);
        $this->getJson('/api/v1/products?brand=%25')->assertOk()->assertJsonCount(0, 'data');
    }
}
