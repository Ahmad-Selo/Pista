<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Offer>
 */
class OfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'discount' => fake()->numberBetween(0, 99),
            'started_at' => fake()->dateTimeBetween('-1 year', '+1 year'),
            'ended_at' => fake()->dateTimeBetween('+1 year', '+2 year'),
        ];
    }
}
