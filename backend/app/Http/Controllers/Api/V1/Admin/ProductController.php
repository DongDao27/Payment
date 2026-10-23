<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArchiveProductRequest;
use App\Http\Requests\CatalogQueryRequest;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\CatalogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function index(CatalogQueryRequest $request, CatalogService $catalog): JsonResponse
    {
        return ApiResponse::page($catalog->query($request->validated(), true)->paginate($request->integer('per_page', 20)), ProductResource::class);
    }

    public function store(ProductRequest $request, CatalogService $catalog): JsonResponse
    {
        return ApiResponse::data(new ProductResource($catalog->save($request->validated(), $request->user()->id)), 201);
    }

    public function destroy(ArchiveProductRequest $request, Product $product, CatalogService $catalog): Response
    {
        $catalog->archive($product, $request->integer('version'));

        return response()->noContent();
    }

    public function update(ProductRequest $request, Product $product, CatalogService $catalog): JsonResponse
    {
        return ApiResponse::data(new ProductResource($catalog->save($request->validated(), $request->user()->id, $product)));
    }
}
