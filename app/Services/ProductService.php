<?php

namespace App\Services;

use App\Enums\Role;
use App\Facades\FileManager;
use App\Http\Requests\ProductCreateRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ProductService
{
    public const UPLOAD_PATH = '/products/';

    private CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    private function getFileContent($product, string $filename = null)
    {
        if ($filename == null) {
            $filename = $product->image;
        }

        $path = StoreService::UPLOAD_PATH . $product->store->id . self::UPLOAD_PATH;
        return FileManager::content($path, $filename);
    }

    private function storeFile($product, $file, string $filename = null)
    {
        $path = StoreService::UPLOAD_PATH . $product->store->id . self::UPLOAD_PATH;
        return FileManager::store($path, $file, $filename);
    }

    private function fileUrl($product, string $filename = null)
    {
        if ($filename == null) {
            $filename = $product->image;
        }

        $path = StoreService::UPLOAD_PATH . $product->store->id . self::UPLOAD_PATH;
        return FileManager::url($path, $filename);
    }

    private function renameFile($product, string $oldFilename, string $newFilename)
    {
        $path = StoreService::UPLOAD_PATH . $product->store->id . self::UPLOAD_PATH;
        return FileManager::rename($path, $oldFilename, $newFilename);
    }

    private function deleteFile($product, string $filename = null)
    {
        if ($filename == null) {
            $filename = $product->image;
        }

        $path = StoreService::UPLOAD_PATH . $product->store->id . self::UPLOAD_PATH;
        return FileManager::delete($path, $filename);
    }

    public function best(int $limit, $categories)
    {
        $products = Product::inStock();

        if ($categories) {
            $products->hasCategories($categories);
        }

        $products->orderBy('popularity', 'desc')->limit($limit);

        return ProductResource::collection($products->get());
    }

    private function newest(int $limit, $categories)
    {
        $products = Product::inStock();

        if ($categories) {
            $products->hasCategories($categories);
        }

        $products->latest()->take($limit);

        return ProductResource::collection($products->get());
    }

    private function popular(int $limit, $categories)
    {
        $products = Product::inStock();

        if ($categories) {
            $products->hasCategories($categories);
        }

        $products->whereNotNull('rate_sum')
            ->whereNotNull('rate_count')
            ->where('rate_count', '!=', 0)
            ->orderByRaw('rate_sum / rate_count desc')
            ->take($limit);

        return ProductResource::collection($products->get());
    }

    private function offers(int $limit, $categories)
    {
        $products = Product::inStock();

        if ($categories) {
            $products->hasCategories($categories);
        }

        $products->discounts()->latest('updated_at')->take($limit);

        return ProductResource::collection($products->get());
    }

    public function highlights(int $limit, $filter)
    {
        $filters = explode(' ', $filter);

        $categories = $this->categoryService->filtersToCategories($filters);

        return [
            'best' => $this->best($limit, $categories),
            'newest' => $this->newest($limit, $categories),
            'most_popular' => $this->popular($limit, $categories),
            'offers' => $this->offers($limit, $categories),
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
        $products = Product::latest()->paginate(20);

        return $products;
    }

    public function store(Store $store, ProductCreateRequest $request)
    {
        $validated = $request->validated();

        $image = $request->file('image');

        $filename = $validated['name'] . '_' . Str::uuid7();

        $product = $store->products()->make($validated);

        $product->image = $this->storeFile(
            $product,
            $image,
            $filename
        );

        $category = Category::where('name', '=', $validated['category'])->first();
        $product->category()->associate($category);

        $product->save();

        $product->inventory()->create([
            'warehouse_id' => $store->warehouse->id,
            'quantity' => $validated['quantity'],
            'last_restocked_date' => now(),
        ]);

        if (isset($validated['discount'])) {
            $product->offer()->create($validated);
        }

        return true;
    }

    public function show(Product $product)
    {
        $product->image = $this->fileUrl($product);

        return $product;
    }

    public function update(ProductUpdateRequest $request, Product $product)
    {
        $validated = $request->validated();

        if (isset($validated['category'])) {
            $category = Category::where('name', '=', $validated['category'])->first();
            $product->category()->associate($category);
        }

        if ($request->hasFile('image')) {
            $this->deleteFile($product);

            $filename = ($request->has('name') ? $validated['name'] : $product->name) . '_' . Str::uuid7();

            $validated['image'] = $this->storeFile(
                $product,
                $request->file('image'),
                $filename
            );
        }

        if ($request->has('name') && !$request->hasFile('image')) {
            $newFilename = $validated['name'] . '_' . Str::uuid7();

            $validated['image'] = $this->renameFile(
                $product,
                $product->image,
                $newFilename
            );
        }

        if (isset($validated['quantity'])) {
            $product->inventory->update([
                'quantity' => $validated['quantity'],
                'last_restocked_date' => now(),
            ]);
        }

        if (isset($validated['discount'])) {
            $product->updateOrCreateOffer($validated);
        }

        return $product->update($validated);
    }

    public function destroy(Product $product)
    {
        $user = Auth::user();

        $store = $product->store;

        $this->ownProductOrAdmin($store, $user);

        $this->deleteFile($product);

        return $product->delete();
    }

    public function search($q, $filter, $order, $direction)
    {
        $qLike = '%' . $q . '%';
        $query = Product::inStock()->whereLike('name', $qLike);

        if ($filter) {
            $filters = explode(' ', $filter);

            $categories = $this->categoryService->filtersToCategories($filters);

            $query->hasCategories($categories);
        }

        if ($order) {
            if ($direction != 'asc') {
                $direction = 'desc';
            }

            $orders = explode(' ', $order);

            foreach ($orders as $column) {
                $query->orderBy($column, $direction);
            }
        }

        return $query->get();
    }
}

