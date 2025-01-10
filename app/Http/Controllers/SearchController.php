<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Http\Resources\StoreResource;
use App\Services\ProductService;
use App\Services\StoreService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    private StoreService $storeService;

    private ProductService $productService;

    public function __construct(StoreService $storeService, ProductService $productService)
    {
        $this->storeService = $storeService;
        $this->productService = $productService;
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $stores = $this->storeService->search($request->q, $request->filter);
        $products = $this->productService->search(
            $request->q,
            $request->filter,
            $request->order,
            $request->direction
        );

        return response()->json([
            'stores' => StoreResource::collection($stores),
            'products' => ProductResource::collection($products),
        ]);
    }
}
