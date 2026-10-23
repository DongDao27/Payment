<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImageUploadRequest;
use App\Http\Resources\ProductImageResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductImageService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProductImageController extends Controller
{
    public function store(ImageUploadRequest $request, Product $product, ProductImageService $images): JsonResponse
    {
        return ApiResponse::data(new ProductImageResource($images->store($product, $request->file('image'), $request->safe()->only(['alt_text', 'sort_order']))), 201);
    }

    public function destroy(Product $product, ProductImage $image, ProductImageService $images): Response
    {
        $images->delete($product, $image);

        return response()->noContent();
    }
}
