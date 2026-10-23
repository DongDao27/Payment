<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RefundRequest;
use App\Models\Order;
use App\Models\Refund;
use App\Services\RefundService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RefundController extends Controller
{
    public function store(RefundRequest $request, Order $order, RefundService $refunds): JsonResponse
    {
        $refund = $refunds->record($order, $request->validated(), $request->user()->id);

        return ApiResponse::data(['id' => $refund->id, 'order_code' => $order->order_code, 'amount' => $refund->amount, 'external_reference' => $refund->external_reference], 201);
    }

    public function evidence(Refund $refund): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($refund->evidence_path), 404);

        return Storage::disk('local')->download($refund->evidence_path);
    }
}
