<?php

namespace App\Services;

use App\Enums\Role;
use App\Facades\FileManager;
use App\Http\Requests\ProductCreateRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Requests\RateRequest;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            ->orderByRate('desc')
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

    private function ownProductOrOwner(Store $store, User $user): bool
    {
        return ($this->ownership($store, $user) || $user->hasRole(Role::OWNER));
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

        $product->translations()->createMany([
            [
                'key' => 'product.name',
                'locale' => 'ar',
                'translation' => $validated['name_ar'],
            ],
            [
                'key' => 'product.description',
                'locale' => 'ar',
                'translation' => $validated['description_ar']
            ]
        ]);

        return true;
    }

    public function show(Product $product)
    {
        return new ProductResource($product);
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

        if (isset($validated['name_ar'])) {
            $product->translations()->where('key', '=', 'product.name')
                ->where('locale', '=', 'ar')->update([
                        'translation' => $validated['name_ar']
                    ]);
        }

        if (isset($validated['description_ar'])) {
            $product->translations()->where('key', '=', 'product.description')->
                where('locale', '=', 'ar')->update([
                        'translation' => $validated['description_ar']
                    ]);
        }

        return $product->update($validated);
    }

    public function destroy(Product $product)
    {
        $user = User::find(Auth::id());

        $store = $product->store;

        throw_unless(
            $this->ownProductOrOwner($store, $user),
            AccessDeniedHttpException::class,
            'access denied',
        );

        return $product->delete();
    }

    public function search(Store|null $store, $q, $filter, $order, $direction)
    {
        if (empty($store)) {
            $query = Product::inStock();
        } else {
            $query = $store->products();
        }

        $qLike = '%' . $q . '%';

        $query->whereLike('name', $qLike);

        if ($filter) {
            $filters = explode(' ', $filter);

            $categories = $this->categoryService->filtersToCategories($filters);

            if (in_array('discount', $filters)) {
                $query->discounts();
            } else if (in_array('full-price', $filters)) {
                $query->fullPrices();
            }

            $query->hasCategories($categories);
        }

        if ($order) {
            if ($direction != 'asc') {
                $direction = 'desc';
            }

            $orders = explode(' ', $order);

            foreach ($orders as $column) {
                if (in_array($column, ['rate', 'price'], true)) {
                    continue;
                }
                if (strcmp($column, 'rate')) {
                    $query->orderByRate($direction);
                } else {
                    $query->orderBy($column, $direction);
                }
            }
        }

        return $query->get();
    }

    public function rate(Product $product, RateRequest $request)
    {
        $validated = $request->validated();

        $user = User::find(Auth::id());

        throw_if(
            $user->hasRole(Role::OWNER),
            AccessDeniedHttpException::class,
            'You are the owner you cannot rate the products'
        );

        throw_if(
            $this->ownProductOrOwner($product->store, $user),
            AccessDeniedHttpException::class,
            'You cannot rate your own products',
        );

        throw_unless(
            $user->hasOrderedProduct($product),
            AccessDeniedHttpException::class,
            'You cannot rate a product you did not order'
        );

        DB::transaction(function () use ($validated, $user, $product) {
            if ($user->hasRatedProduct($product)) {
                $rate = $user->rate($product);

                $product->rate_sum += $validated['rate'] - $rate;

                $user->rates()->updateExistingPivot($product->id, $validated);
            } else {
                $user->rates()->attach(
                    $product->id,
                    ['rate' => $validated['rate']]
                );

                $product->rate_sum += $validated['rate'];
                $product->rate_count++;
            }

            $product->save();
        });

        return $product;
    }
}

