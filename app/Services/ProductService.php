<?php

namespace App\Services;

use App\Enums\Role;
use App\Http\Requests\ProductCreateRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ProductService
{
    public const UPLOAD_PATH = '/products';

    private FileService $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    private function getFileContent($product, string $filename = null)
    {
        if ($filename == null) {
            $filename = $product->image;
        }

        $path = StoreService::UPLOAD_PATH . $product->store->id . self::UPLOAD_PATH;
        return $this->fileService->getContent($path, $filename);
    }

    private function storeFile($product, $file, string $filename = null)
    {
        $path = StoreService::UPLOAD_PATH . $product->store->id . self::UPLOAD_PATH;
        return $this->fileService->store($path, $file, $filename);
    }

    private function fileUrl($product, string $filename = null)
    {
        if ($filename == null) {
            $filename = $product->image;
        }

        $path = StoreService::UPLOAD_PATH . $product->store->id . self::UPLOAD_PATH;
        return $this->fileService->url($path, $filename);
    }

    private function renameFile($product, string $oldFilename, string $newFilename)
    {
        $path = StoreService::UPLOAD_PATH . $product->store->id . self::UPLOAD_PATH;
        return $this->fileService->rename($path, $oldFilename, $newFilename);
    }

    private function deleteFile($product, string $filename = null)
    {
        if ($filename == null) {
            $filename = $product->image;
        }

        $path = StoreService::UPLOAD_PATH . $product->store->id . self::UPLOAD_PATH;
        return $this->fileService->delete($path, $filename);
    }

    private function getNewest(int $limit, $query = null)
    {
        if ($query == null) {
            $products = Product::whereHas(
                'inventory',
                function ($query) {
                    return $query->where('quantity', '>', 0);
                }
            )->latest()->take($limit)->get();
        } else {
            $products = $query->latest()->take($limit)->get();
        }

        foreach ($products as $product) {
            $product->image = $this->fileUrl($product);
        }

        return $products;
    }

    private function getMostPopular(int $limit, $query = null)
    {
        if ($query == null) {
            $products = Product::whereHas(
                'inventory',
                function ($query) {
                    return $query->where('quantity', '>', 0);
                }
            )->whereNotNull('rate_sum')
                ->whereNotNull('rate_count')
                ->where('rate_count', '!=', 0)
                ->orderByRaw('(rate_sum / rate_count) DESC')
                ->take($limit)->get();
        } else {
            $products = $query->whereNotNull('rate_sum')
                ->whereNotNull('rate_count')
                ->where('rate_count', '!=', 0)
                ->orderByRaw('(rate_sum / rate_count) DESC')
                ->take($limit)->get();
        }

        foreach ($products as $product) {
            $product->image = $this->fileUrl($product);
        }

        return $products;
    }

    private function getOffers(int $limit, $query = null)
    {
        if ($query == null) {
            $products = Product::whereHas(
                'inventory',
                function ($query) {
                    return $query->where('quantity', '>', 0);
                }
            )->where('discount', '>', 0)
                ->latest('updated_at')->take($limit)->get();
        } else {
            $products = $query->where('discount', '>', 0)
                ->latest('updated_at')->take($limit)->get();
        }

        foreach ($products as $product) {
            $product->image = $this->fileUrl($product);
        }

        return $products;
    }

    private function getProductsForUser()
    {
        $query = Product::whereHas(
            'inventory',
            function ($query) {
                return $query->where('quantity', '>', 0);
            }
        );

        return [
            'newest' => $this->getNewest(5, $query),
            'most_popular' => $this->getMostPopular(5, $query),
            'offers' => $this->getOffers(5, $query),
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
            $result = Product::latest()->paginate(20);
        } else {
            $result = $this->getProductsForUser();
        }

        $result = $this->getProductsForUser();

        return $result;
    }

    public function store(Store $store, ProductCreateRequest $request)
    {
        $validated = $request->validated();

        $image = $request->file('image');

        $filename = $validated['name'] . '_' . Str::uuid7();

        $validated['image'] = $filename . '.' . $image->getClientOriginalExtension();

        $product = $store->products()->create($validated);

        $product->inventory()->create([
            'warehouse_id' => $store->warehouse->id,
            'quantity' => $validated['quantity'],
            'last_restocked_date' => now(),
        ]);

        $this->storeFile(
            $product,
            $image,
            $filename
        );

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

            $query->whereIn('category', $filters);
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

