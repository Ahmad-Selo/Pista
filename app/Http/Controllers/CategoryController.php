<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    private CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $categories = $this->categoryService->index();

        return response()->json($categories);
    }

    public function store(CategoryRequest $request)
    {
        $created = $this->categoryService->store($request);

        if (!$created) {
            return response()->json([
                'message' => 'An error occurred',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Category created successfully',
        ]);
    }

    public function update(Category $category, CategoryRequest $request)
    {
        $updated = $this->categoryService->update($category, $request);

        if (!$updated) {
            return response()->json([
                'message' => 'An error occurred',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Category updated successfully',
        ]);
    }

    public function destroy(Category $category)
    {
        try {
            $this->categoryService->destroy($category);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') {
                return response()->json([
                    'message' => 'Cannot delete this category because it is associated with other products',
                ], Response::HTTP_BAD_REQUEST);
            } else {
                return response()->json([
                    'message' => 'An error occurred',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }
}
