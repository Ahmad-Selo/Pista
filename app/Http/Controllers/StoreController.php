<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCreateRequest;
use App\Http\Requests\StoreUpdateRequest;
use App\Models\Store;
use App\Services\StoreService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StoreController extends Controller
{
    private StoreService $storeService;

    public function __construct(StoreService $storeService)
    {
        $this->storeService = $storeService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stores = $this->storeService->index();

        return response()->json([
            'stores' => $stores,
        ]);
    }

    public function products(Store $store)
    {
        $products = $this->storeService->products($store);

        return response()->json([
            'products' => $products,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCreateRequest $request)
    {
        $created = $this->storeService->store($request);

        if (!$created) {
            return response()->json([
                'message' => 'An error occurred',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Store created successfully',
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store)
    {
        $this->storeService->show($store);

        return response()->json([
            'store' => $store,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreUpdateRequest $request, Store $store)
    {
        $updated = $this->storeService->update($request, $store);

        if (!$updated) {
            return response()->json([
                'message' => 'An error occurred',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Store updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        $deleted = $this->storeService->destroy($store);

        if (!$deleted) {
            return response()->json([
                'message' => 'An error occurred',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        }

        return response()->json([
            'message' => 'Store deleted successfully',
        ]);
    }
}
