<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->has(Address::factory())->count(10)->create();
        Store::factory()->has(Address::factory())->count(10)->create();
        Product::factory(10)->create();
    }
}
