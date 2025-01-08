<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Warehouse>
 */
class WarehouseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->companySuffix(),
            'retrieval_time' =>fake()->numberBetween(300, 3600),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Warehouse $warehouse) {
            $warehouse->address()->create(Address::factory()->make()->toArray());
        });
    }
}
