<?php

namespace App\Services;

use App\Enums\Role;
use App\Http\Requests\StoreCreateRequest;
use App\Http\Requests\StoreUpdateRequest;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class StoreService
{
    private function ownership(Store $store, User $user): bool
    {
        return ($store->user->id == $user->id);
    }

    private function storeOwnerOrAdmin(Store $store, User $user): void
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
            $stores = Store::latest()->paginate(20);
        } else {
            $stores = $user->stores;
        }

        return $stores;
    }

    public function products(Store $store)
    {
        $products = $store->products()->paginate(20);

        return $products;
    }

    public function store(StoreCreateRequest $request)
    {
        $validated = $request->validated();

        Store::create($validated);

        return true;
    }

    public function show(Store $store)
    {
        $user = Auth::user();

        $this->storeOwnerOrAdmin($store, $user);

        return $store;
    }

    public function update(StoreUpdateRequest $request, Store $store)
    {
        $validated = $request->validated();

        return $store->update($validated);
    }

    public function destroy(Store $store)
    {
        $user = Auth::user();

        $this->storeOwnerOrAdmin($store, $user);

        return $store->delete();
    }

}