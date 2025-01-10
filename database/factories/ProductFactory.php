<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'description' => fake()->text(255),
            'price' => fake()->randomFloat(),
            'discount' => fake()->randomNumber(2),
            'popularity' => fake()->randomNumber(5),
            'store_id' => Store::pluck('id')->random(),
            'image' => fake()->imageUrl(),
            'category' => Category::factory(1)->create(),
            'rate_sum' => fake()->optional()->randomNumber(3),
            'rate_count' => fake()->optional()->numberBetween(0, 100),
            'created_at' => fake()->dateTimeBetween('-6 hours'),
            'updated_at' => fake()->dateTimeBetween('now', '+3 hours'),
        ];
    }
}
