<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StoreResource;
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

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $categories = $this->categoryService->index();
        $stores = $this->storeService->search($request->q, $request->filter);
        $products = $this->productService->search(
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
