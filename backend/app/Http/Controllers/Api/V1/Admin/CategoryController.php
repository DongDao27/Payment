<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogQueryRequest;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Product;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function index(CatalogQueryRequest $request): JsonResponse
    {
        return ApiResponse::page(Category::orderBy('sort_order')->orderBy('id')->paginate($request->integer('per_page', 20)), CategoryResource::class);
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        return ApiResponse::data(new CategoryResource(Category::create($request->validated())), 201);
    }

    public function update(CategoryRequest $request, Category $category): JsonResponse
    {
        $category = DB::transaction(function () use ($request, $category) {
            $category = Category::whereKey($category->id)->lockForUpdate()->firstOrFail();
            if ($category->slug === 'sach-giao-khoa' && $request->has('slug') && $request->slug !== $category->slug) {
                throw ValidationException::withMessages(['slug' => 'The textbook category identifier is reserved.']);
            }
            $category->update($request->validated());
            Product::where('category_id', $category->id)->increment('catalog_version');

            return $category;
        }, 3);

        return ApiResponse::data(new CategoryResource($category));
    }
}
