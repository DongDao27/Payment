<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return ['slug' => fake()->unique()->slug(), 'name' => 'Demo '.fake()->word(), 'is_active' => true];
    }
}
