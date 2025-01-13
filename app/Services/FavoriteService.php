<?php

namespace App\Services;

use App\Exceptions\DuplicateViolation;
use App\Exceptions\NotFoundException;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\User;

class FavoriteService
{
    public function index(User $user)
    {
        $favorites = $user->favorites;

        return ProductResource::collection($favorites);
    }

    public function store(User $user, Product $product)
    {
        throw_if(
            $user->favorites()->where('product_id', $product->id)->exists(),
            DuplicateViolation::class,
            'product is already in user favorites'
        );

        $user->favorites()->attach($product->id);

        return true;
    }

    public function destroy(User $user, Product $product)
    {
        throw_unless(
            $user->favorites()->where('product_id', $product->id)->exists(),
            NotFoundException::class,
            'product is not in user favorites'
        );

        $user->favorites()->detach($product->id);

        return true;
    }
}