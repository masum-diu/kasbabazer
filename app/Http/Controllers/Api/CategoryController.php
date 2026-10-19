<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Active root categories with their active children (two queries, no N+1).
     */
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();

        return ApiResponse::success(CategoryResource::collection($categories));
    }

    public function show(Category $category): JsonResponse
    {
        abort_unless($category->is_active, 404);

        return ApiResponse::success(new CategoryResource($category->load('children')));
    }
}
