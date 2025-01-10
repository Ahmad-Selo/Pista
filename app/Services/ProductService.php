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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ProductService
{
    public const UPLOAD_PATH = '/products/';

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

    public function best(int $limit)
    {
        $products = Product::whereHas(
            'inventory',
            function ($query) {
                return $query->where('quantity', '>', 0);
            }
        )->orderBy('popularity', 'desc')
            ->limit($limit)->get();

        return ProductResource::collection($products);
    }

    private function newest(int $limit)
    {
        $products = Product::whereHas(
            'inventory',
            function ($query) {
                return $query->where('quantity', '>', 0);
            }
        )->latest()->take($limit)->get();

        return ProductResource::collection($products);
    }

    private function popular(int $limit)
    {
        $products = Product::whereHas(
            'inventory',
            function ($query) {
                return $query->where('quantity', '>', 0);
            }
        )->whereNotNull('rate_sum')
            ->whereNotNull('rate_count')
            ->where('rate_count', '!=', 0)
            ->orderByRaw('rate_sum / rate_count desc')
            ->take($limit)->get();

        return ProductResource::collection($products);
    }

    private function offers(int $limit)
    {
        $products = Product::whereHas(
            'inventory',
            function ($query) {
                return $query->where('quantity', '>', 0);
            }
        )->where('discount', '>', 0)
            ->latest('updated_at')->take($limit)->get();

        return ProductResource::collection($products);
    }

    public function highlights()
    {
        return [
            'best' => $this->best(5),
            'newest' => $this->newest(5),
            'most_popular' => $this->popular(5),
            'offers' => $this->offers(5),
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

        $filename = $validated['name'] . '_' . Str::uuid7() . '.' . $image->getClientOriginalExtension();

        $product = $store->products()->make($validated);

        $product->image = $this->storeFile(
            $product,
            $image,
            $filename
        );

        $product->save();

        $product->inventory()->create([
            'warehouse_id' => $store->warehouse->id,
            'quantity' => $validated['quantity'],
            'last_restocked_date' => now(),
        ]);

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
        $query = Product::whereLike('name', '%' . $q . '%');

        if ($filter) {
            $filters = explode(' ', $filter);

            foreach ($filters as $name) {
                $categories[] = Category::where('name', $name)->value('id');
            }

            $query->whereIn('category_id', $categories);
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

