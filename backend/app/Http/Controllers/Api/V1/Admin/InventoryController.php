<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogQueryRequest;
use App\Http\Requests\InventoryAdjustmentRequest;
use App\Http\Resources\InventoryResource;
use App\Models\Product;
use App\Services\CatalogService;
use App\Services\InventoryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    public function index(CatalogQueryRequest $request, CatalogService $catalog): JsonResponse
    {
        return ApiResponse::page($catalog->query($request->validated(), true)->paginate($request->integer('per_page', 20)), InventoryResource::class);
    }

    public function store(InventoryAdjustmentRequest $request, Product $product, InventoryService $inventory): JsonResponse
    {
        return ApiResponse::data(new InventoryResource($inventory->adjust($product, $request->integer('delta'), $request->validated('reason'), $request->validated('request_key'), $request->user()->id)), 201);
    }
}
