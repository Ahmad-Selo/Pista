<?php

use App\Models\Store;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description');
            $table->unsignedInteger('quantity');
            $table->float('price');
            $table->float('discount')->default(0.0);
            $table->unsignedBigInteger('popularity')->default(0);
            $table->foreignIdFor(Store::class)->constrained()->cascadeOnDelete();
            $table->string('photo');
            $table->string('category');
            $table->float('rate_sum')->nullable();
            $table->unsignedInteger('rate_count')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
