<?php

namespace Database\Factories;

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
            'quantity' => fake()->numberBetween(0, 1024),
            'price' => fake()->randomFloat(),
            'discount' => fake()->randomNumber(2),
            'popularity' => fake()->randomNumber(5),
            'photo' => fake()->imageUrl(),
            'category'=>fake()->word(),
            'store_id' => fake()->randomElement(Store::pluck('id')->toArray()),
            'created_at' => fake()->dateTimeBetween('-6 hours'),
            'updated_at' => fake()->dateTimeBetween('now', '+3 hours'),
        ];
    }
}
