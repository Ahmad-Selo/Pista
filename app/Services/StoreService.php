<?php

namespace App\Services;

use App\Enums\Role;
use App\Facades\FileManager;
use App\Http\Requests\StoreCreateRequest;
use App\Http\Requests\StoreUpdateRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StoreResource;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StoreService
{
    public const UPLOAD_PATH = 'uploads/stores/';

    private CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

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

    private function getDuration($longitude, $latitude)
    {
        $response = Http::get(
            config('services.map.osrm.route') . '36.2920484,33.4953687;' . $longitude . ',' . $latitude,
            ['overview' => 'false']
        );

        throw_if(
            $response->failed(),
            HttpException::class,
            "Failed to fetch data from the OSRM API. Status: {$response->status()}, Body: {$response->body()}"
        );

        return $response->json('routes')[0]['duration'];
    }

    private function getFileContent($store, string $filename = null)
    {
        if ($filename == null) {
            $filename = $store->image;
        }
        return FileManager::content(self::UPLOAD_PATH . $store->id, $filename);
    }

    private function storeFile($store, $file, string $filename = null)
    {
        return FileManager::store(self::UPLOAD_PATH . $store->id, $file, $filename);
    }

    private function fileUrl($store, string $filename = null)
    {
        if ($filename == null) {
            $filename = $store->image;
        }

        return FileManager::url(self::UPLOAD_PATH . $store->id, $filename);
    }

    private function renameFile($store, string $oldFilename, string $newFilename)
    {
        return FileManager::rename(self::UPLOAD_PATH . $store->id, $oldFilename, $newFilename);
    }

    private function deleteDirectoryOrFile($store, string $filename = null)
    {
        return FileManager::delete(self::UPLOAD_PATH . $store->id, $filename);
    }

    public function newest(int $limit, $filter)
    {
        $stores = Store::latest();

        if ($filter) {
            $filters = explode(' ', $filter);

            $categories = $this->categoryService->filtersToCategories($filters);

            $stores = Store::hasCategories($categories);
        }

        $stores->limit($limit);

        return StoreResource::collection($stores->get());
    }

    public function index()
    {
        $stores = Store::latest()->paginate(20);

        return $stores;
    }

    public function products(Store $store, $filter)
    {
        $products = $store->products();

        if ($filter) {
            $filters = explode(' ', $filter);

            $categories = $this->categoryService->filtersToCategories($filters);

            if (in_array('discount', $filters)) {
                $products->discounts();
            } else if (in_array('full-price', $filters)) {
                $products->fullPrices();
            }

            if ($categories) {
                $products->hasCategories($categories);
            }
        }

        $storeCategories = $store->categories()->distinct();

        return [
            'categories' => CategoryResource::collection($storeCategories->get()),
            'products' => ProductResource::collection($products->get()),
        ];
    }

    public function availableProducts(Store $store, $filter)
    {
        $products = $store->products()->inStock();

        if ($filter) {
            $filters = explode(' ', $filter);

            $categories = $this->categoryService->filtersToCategories($filters);

            $products->hasCategories($categories)->get();
        }

        $storeCategories = $store->categories()->distinct();

        return [
            'categories' => CategoryResource::collection($storeCategories->get()),
            'products' => ProductResource::collection($products->get()),
        ];
    }

    public function store(StoreCreateRequest $request)
    {
        $validated = $request->validated();

        $image = $request->file('image');

        $filename = $validated['store']['name'] . '_' . Str::uuid7();

        $validated['warehouse']['retrieval_time'] = $this->getDuration(
            $validated['address']['longitude'],
            $validated['address']['latitude']
        );

        $store = Store::create($validated['store']);

        $store->update([
            'image' => $this->storeFile(
                $store,
                $image,
                $filename
            )
        ]);

        $store->warehouse()->create($validated['warehouse'])
            ->address()->create($validated['address']);

        return true;
    }

    public function show(Store $store)
    {
        $user = Auth::user();

        $this->storeOwnerOrAdmin($store, $user);

        return new StoreResource($store);
    }

    public function update(StoreUpdateRequest $request, Store $store)
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $this->deleteDirectoryOrFile($store, $store->image);

            $filename = ($request->has('store_name') ? $validated['store']['name'] : $store->name) . '_' . Str::uuid7();

            $validated['store']['image'] = $this->storeFile(
                $store,
                $request->file('image'),
                $filename
            );
        }

        if ($request->has('store_name') && !$request->hasFile('image')) {
            $newFilename = $validated['store']['name'] . '_' . Str::uuid7();

            $validated['store']['image'] = $this->renameFile(
                $store,
                $store->image,
                $newFilename
            );
        }

        if ($request->has('address_name')) {
            $validated['address']['retrieval_time'] = $this->getDuration(
                $validated['address']['longitude'],
                $validated['address']['latitude']
            );
        }

        if (isset($validated['store'])) {
            $store->update($validated['store']);
        }
        if (isset($validated['warehouse'])) {
            $store->warehouse->update($validated['warehouse']);
        }
        if (isset($validated['address'])) {
            $store->warehouse->address->update($validated['address']);
        }

        return true;
    }

    public function destroy(Store $store)
    {
        $user = Auth::user();

        $this->storeOwnerOrAdmin($store, $user);

        $store->products()->delete();

        return $store->delete();
    }

    public function search($q, $filter)
    {
        $qLike = '%' . $q . '%';

        if ($filter) {
            $filters = explode(' ', $filter);

            $categories = $this->categoryService->filtersToCategories($filters);

            $queryA = Store::whereLike('name', $qLike)->hasCategories($categories);

            $queryB = Store::whereHas('products', function ($query) use ($qLike, $categories) {
                $query->whereLike('name', $qLike)->hasCategories($categories);
            });


        } else {
            $queryA = Store::whereLike('name', $qLike);

            $queryB = Store::whereHas('products', function ($query) use ($qLike) {
                $query->whereLike('name', $qLike);
            });
        }

        $query = $queryA->union($queryB);

        return $query->orderBy('name')->get();
    }

}