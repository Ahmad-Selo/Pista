<?php

namespace App\Services;

use App\Enums\Role;
use App\Http\Requests\ProductCreateRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ProductService
{
    private function getProductsForAdmin()
    {
        return Product::latest()->paginate(20);
    }

    private function getNewest(int $limit)
    {
        return Product::latest()->where('quantity', '>', 0)->take($limit)->get();
    }

    private function getMostPopular(int $limit)
    {
        return Product::orderBy('popularity', 'desc')
            ->where('quantity', '>', 0)
            ->take($limit)->get();
    }

    private function getOffers(int $limit)
    {
        return Product::latest('updated_at')
            ->where('quantity', '>', 0)
            ->where('discount', '>', 0)
            ->take($limit)->get();
    }

    private function getProductsForUser()
    {
        return [
            'newest' => $this->getNewest(5),
            'most_popular' => $this->getMostPopular(5),
            'offers' => $this->getOffers(5),
        ];
    }

    private function ownership(Store $store, User $user)
    {
        return $store->user->id == $user->id;
    }

    private function ownProductOrAdmin(Store $store, User $user): void
    {
        throw_if(
            !$this->ownership($store, $user) && !$user->hasRole(Role::ADMIN),
            AccessDeniedHttpException::class,
            'access denied',
        );
    }

    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole(Role::ADMIN)) {
            $result = $this->getProductsForAdmin();
        } else {
            $result = $this->getProductsForUser();
        }

        return $result;
    }

    public function store(Store $store, ProductCreateRequest $request)
    {
        $validated = $request->validated();

        $validated['store_id'] = $store->id;

        Product::create($validated);

        return true;
    }

    public function show(Product $product)
    {
        $user = Auth::user();

        $rate_sum = $product->rate_sum;
        $rate_count = $product->rate_count;

        if ($rate_sum && $rate_count > 0) {
            $rate = $rate_sum / $rate_count;
        }

        $result = [
            'name' => $product->name,
            'description' => $product->description,
            'quantity' => $product->quantity,
            'price' => $product->price,
            'discount' => $product->discount,
            'store_id' => $product->store->id,
            'photo' => $product->photo,
            'category' => $product->category,
            'rate' => $rate,
        ];

        if ($user->hasRole(Role::ADMIN)) {
            $result['created_at'] = $product->created_at;
            $result['updated_at'] = $product->updated_at;
        }

        return $result;
    }

    public function update(ProductUpdateRequest $request, Product $product)
    {
        $validated = $request->validated();

        return $product->update($validated);
    }

    public function destroy(Product $product)
    {
        $user = Auth::user();

        $store = $product->store;

        $this->ownProductOrAdmin($store, $user);

        return $product->delete();
    }
}