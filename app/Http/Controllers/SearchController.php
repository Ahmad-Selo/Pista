<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StoreResource;
use App\Models\Store;
use App\Services\CategoryService;
use App\Services\ProductService;
use App\Services\StoreService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    private StoreService $storeService;
    private ProductService $productService;
    private CategoryService $categoryService;

    public function __construct(StoreService $storeService, ProductService $productService, CategoryService $categoryService)
    {
        $this->storeService = $storeService;
        $this->productService = $productService;
        $this->categoryService = $categoryService;
    }

    public function storeProducts(Store $store, Request $request)
    {
        $categories = $store->categories()->get();
        $products = $this->productService->search(
            $store,
            $request->q,
            $request->filter,
            $request->order,
            $request->direction
        );

        return response()->json([
            'categories' => CategoryResource::collection($categories),
            'products' => ProductResource::collection($products),
        ]);
    }

    public function products(Request $request)
    {
        $categories = $this->categoryService->index(true);
        $stores = $this->storeService->search($request->q, $request->filter);
        $products = $this->productService->search(
            null,
            $request->q,
            $request->filter,
            $request->order,
            $request->direction
        );

        return response()->json([
            'categories' => CategoryResource::collection($categories),
            'stores' => StoreResource::collection($stores),
            'products' => ProductResource::collection($products),
        ]);
    }
}
