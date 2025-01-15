<?php

namespace App\Services;

use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategoryService
{

    public function filtersToCategories($filters)
    {
        $categories = Category::whereIn('name', $filters)->pluck('id')->toArray();

        return $categories;
    }

    public function index(bool $withoutEmpty = false)
    {
        if ($withoutEmpty) {
            $categories = Category::withoutEmpty();
        } else {
            $categories = Category::latest();
        }

        return CategoryResource::collection($categories->get());
    }

    public function store(CategoryRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $category = Category::create($validated);
            
            $category->translations()->create([
                'key' => 'category.name',
                'locale' => 'ar',
                'translation' => $validated['name_ar']
            ]);
        });

        return true;
    }

    public function update(Category $category, CategoryRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($category, $validated) {
            $category->update($validated);

            $category->translations()->where('key', '=', 'category.name')
                ->where('locale', '=', 'ar')->update([
                        'translation' => $validated['name_ar']
                    ]);
        });

        return true;
    }

    public function destroy(Category $category)
    {
        return $category->delete();
    }

}