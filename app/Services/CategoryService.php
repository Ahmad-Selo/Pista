<?php

namespace App\Services;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;

class CategoryService
{

    public function filtersToCategories($filters)
    {
        $categories = Category::whereIn('name', $filters)->pluck('id')->toArray();

        return $categories;
    }

    public function index()
    {
        $categories = Category::latest()->paginate();

        return $categories;
    }

    public function store(CategoryRequest $request)
    {
        $validated = $request->validated();

        return Category::create($validated);
    }

    public function update(Category $category, CategoryRequest $request)
    {
        $validated = $request->validated();

        return $category->update($validated);
    }

    public function destroy(Category $category)
    {
        return $category->delete();
    }

}