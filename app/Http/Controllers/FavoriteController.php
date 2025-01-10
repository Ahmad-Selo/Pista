<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Services\FavoriteService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FavoriteController extends Controller
{

    private FavoriteService $favoriteService;

    public function __construct(FavoriteService $favoriteService)
    {
        $this->favoriteService = $favoriteService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(User $user)
    {
        $favorites = $this->favoriteService->index($user);

        return response()->json([
            'favorites' => $favorites,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(User $user, Product $product)
    {
        $created = $this->favoriteService->store($user, $product);

        if (!$created) {
            return response()->json([
                'message' => 'An error occurred',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Favorite created successfully',
        ], Response::HTTP_CREATED);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user, Product $product)
    {
        $deleted = $this->favoriteService->destroy($user, $product);

        if (!$deleted) {
            return response()->json([
                'message' => 'An error occurred',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }
}
