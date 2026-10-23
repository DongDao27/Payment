<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogQueryRequest;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\CartResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function store(CreateOrderRequest $request, CheckoutService $checkout): JsonResponse
    {
        $result = $checkout->create($request->user(), $request->validated('quote_id'), $request->header('Idempotency-Key'));

        return ApiResponse::data($result['data'], $result['replayed'] ? 200 : 201, ['replayed' => $result['replayed']]);
    }

    public function index(CatalogQueryRequest $request): JsonResponse
    {
        return ApiResponse::page(Order::where('user_id', $request->user()->id)->with('items')->orderByDesc('created_at')->orderByDesc('id')->paginate($request->integer('per_page', 20)), OrderResource::class);
    }

    public function show(Request $request, string $order): JsonResponse
    {
        return ApiResponse::data(new OrderResource($this->owned($request, $order)));
    }

    public function cancel(Request $request, string $order, OrderService $orders): JsonResponse
    {
        return ApiResponse::data(new OrderResource($orders->cancel($this->owned($request, $order), $request->user()->id)));
    }

    public function reorder(Request $request, string $order, CartService $carts): JsonResponse
    {
        $result = $carts->reorder($request->user(), $this->owned($request, $order));
        $data = (new CartResource($result['cart']))->resolve($request);
        $data['warnings'] = array_merge($data['warnings'], $result['warnings']);

        return ApiResponse::data($data);
    }

    private function owned(Request $request, string $code): Order
    {
        return Order::where('order_code', $code)->where('user_id', $request->user()->id)->with('items')->firstOrFail();
    }
}
