<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'sku' => fake()->unique()->bothify('DEMO-????-#####'),
            'slug' => fake()->unique()->slug(),
            'name' => 'Demo '.fake()->words(3, true),
            'list_price' => 35000,
            'stock_on_hand' => 10,
            'stock_reserved' => 0,
            'is_active' => true,
        ];
    }
}
