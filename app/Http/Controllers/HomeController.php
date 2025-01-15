<?php

namespace App\Http\Controllers;

use App\Services\CategoryService;
use App\Services\ProductService;
use App\Services\StoreService;
use Illuminate\Http\Request;

class HomeController extends Controller
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

    public function __invoke(Request $request)
    {
        $result = $this->productService->highlights(5, $request->filter);

        $result['brands'] = $this->storeService->newest(5, $request->filter);

        $result['categories'] = $this->categoryService->index(true);

        return response()->json($result);
    }
}
