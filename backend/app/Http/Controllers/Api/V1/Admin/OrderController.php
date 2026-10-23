<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminOrderQueryRequest;
use App\Http\Requests\FulfillmentRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function index(AdminOrderQueryRequest $request): JsonResponse
    {
        $query = Order::with('items');
        foreach (['fulfillment_method', 'payment_status', 'fulfillment_status'] as $key) {
            if ($request->has($key)) {
                $query->where($key, $request->validated($key));
            }
        }
        if ($request->filled('q')) {
            $q = $request->validated('q');
            $query->where(fn ($query) => $query->whereRaw('LOCATE(?, order_code) > 0', [$q])->orWhereRaw('LOCATE(?, recipient_name) > 0', [$q])->orWhereRaw('LOCATE(?, recipient_phone) > 0', [$q]));
        }
        foreach (['from' => '>=', 'to' => '<'] as $key => $operator) {
            if ($request->has($key)) {
                $query->where('created_at', $operator, CarbonImmutable::parse($request->validated($key), 'Asia/Ho_Chi_Minh')->utc());
            }
        }

        return ApiResponse::page($query->orderByDesc('created_at')->orderByDesc('id')->paginate($request->integer('per_page', 20)), OrderResource::class);
    }

    public function show(Order $order): JsonResponse
    {
        return ApiResponse::data(new OrderResource($order->load('items')));
    }

    public function update(FulfillmentRequest $request, Order $order, OrderService $orders): JsonResponse
    {
        return ApiResponse::data(new OrderResource($orders->fulfill($order, $request->validated('target_status'), $request->integer('version'), $request->user()->id)));
    }
}
