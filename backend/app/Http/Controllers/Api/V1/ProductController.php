<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogQueryRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\CatalogService;
use App\Services\StorefrontSnapshot;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(CatalogQueryRequest $request, CatalogService $catalog): JsonResponse
    {
        return ApiResponse::page($catalog->query($request->validated())->paginate($request->integer('per_page', 20)), ProductResource::class);
    }

    public function show(Product $product): JsonResponse
    {
        abort_unless($product->is_active && $product->category->is_active, 404);

        return ApiResponse::data(new ProductResource($product->load(['category', 'images'])));
    }

    public function storefront(StorefrontSnapshot $snapshot): JsonResponse
    {
        return ApiResponse::data($snapshot->data())->header('Cache-Control', 'public, max-age=0, s-maxage=20, stale-while-revalidate=20');
    }

    public function home(Request $request, CatalogService $catalog, StorefrontSnapshot $snapshot): JsonResponse
    {
        $data = $snapshot->data();
        if ($request->user()?->role === 'customer') {
            $data['recommended'] = ProductResource::collection($catalog->recommendations($request->user()->id))->resolve();
        }

        return ApiResponse::data($data);
    }
}
