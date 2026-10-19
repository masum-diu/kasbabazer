<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function store(CategoryRequest $request): JsonResponse
    {
        $data = $request->safe()->except('image');
        $data['slug'] = $data['slug'] ?? $this->uniqueSlug($data['name']);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category = Category::create($data);

        return ApiResponse::created(new CategoryResource($category), 'Category created successfully');
    }

    public function update(CategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($data);

        return ApiResponse::success(new CategoryResource($category->fresh()), 'Category updated successfully');
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return ApiResponse::success(null, 'Category deleted successfully');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i = 2;

        while (Category::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
