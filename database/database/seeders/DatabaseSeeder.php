<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = User::factory(20)->create();
        $stores = Store::factory(10)->create();
        $category=Category::factory(10)->create();
        $products = Product::factory(10)->create();
        $offer = Offer::factory(10)->create();

        foreach ($stores as $store) {
            Warehouse::factory()->create([
                'store_id' => $store->id,
            ]);
        }

        foreach ($products as $product) {
            Inventory::factory()->create([
                'warehouse_id' => $product->store->warehouse->id,
                'product_id' => $product->id,
            ]);
        }
    }
}
