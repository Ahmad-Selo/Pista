<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\ProductService;
use App\Services\StoreService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    private StoreService $storeService;

    private ProductService $productService;

    public function __construct(StoreService $storeService ,ProductService $productService)
    {
        $this->productService = $productService;
        $this->storeService = $storeService;
    }

    public function __invoke()
    {
        $result = $this->productService->highlights();

        $result['brands'] = $this->storeService->newest(5);

        $result['categories'] = CategoryResource::collection(Category::all());

        return $result;
    }
}
