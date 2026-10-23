<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentAttemptRequest;
use App\Models\Order;
use App\Services\PaymentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(PaymentAttemptRequest $request, string $order, PaymentService $payments): JsonResponse
    {
        $query = Order::where('order_code', $order);
        $admin = $request->is('api/v1/admin/*');
        if (! $admin) {
            $query->where('user_id', $request->user()->id);
        }
        $attempt = $payments->create($query->firstOrFail(), $request->user()->id, $request->header('Idempotency-Key'), $admin);

        return ApiResponse::data($payments->present($attempt, $request->ip()));
    }

    public function show(Request $request, string $order, PaymentService $payments): JsonResponse
    {
        $query = Order::where('order_code', $order);
        if (! $request->is('api/v1/admin/*')) {
            $query->where('user_id', $request->user()->id);
        }

        return ApiResponse::data($payments->status($query->firstOrFail()));
    }
}
