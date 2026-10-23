<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Services\CartService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CartController extends Controller
{
    public function index(Request $request, CartService $carts): JsonResponse
    {
        return ApiResponse::data(new CartResource($carts->get($request->user())));
    }

    public function store(CartItemRequest $request, CartService $carts): JsonResponse
    {
        return ApiResponse::data(new CartResource($carts->add($request->user(), $request->integer('product_id'), $request->integer('quantity'))));
    }

    public function update(CartItemRequest $request, CartItem $item, CartService $carts): JsonResponse
    {
        return ApiResponse::data(new CartResource($carts->set($request->user(), $item, $request->integer('quantity'))));
    }

    public function destroy(Request $request, CartItem $item, CartService $carts): Response
    {
        $carts->remove($request->user(), $item);

        return response()->noContent();
    }
}
