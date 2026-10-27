<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Payments\PaymentGateway;
use App\Services\PaymentService;
use App\Services\ReconciliationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class VnpayController extends Controller
{
    public function ipn(Request $request, PaymentGateway $gateway, PaymentService $payments): JsonResponse
    {
        try {
            $verified = $gateway->verifyCallback($request->query());
            $code = $verified ? $payments->apply($verified, 'ipn') : '97';
        } catch (Throwable) {
            Log::error('VNPAY callback could not be applied. Inspect database/queue health with request_id.');
            $code = '99';
        }
        $messages = ['00' => 'Confirm Success', '01' => 'Order not found', '02' => 'Order already confirmed', '04' => 'Invalid amount', '97' => 'Invalid signature', '99' => 'Unknown error'];

        return response()->json(['RspCode' => $code, 'Message' => $messages[$code]]);
    }

    public function returned(Request $request, PaymentGateway $gateway, ReconciliationService $reconciliation): JsonResponse
    {
        $verified = $gateway->verifyCallback($request->query());
        if (! $verified) {
            return ApiResponse::error('INVALID_PAYMENT_RETURN', 'Payment return signature is invalid.', 422);
        }
        $order = $reconciliation->confirmReturn($verified);
        if (! $order) {
            return ApiResponse::error('PAYMENT_NOT_FOUND', 'No payment attempt matches this return.', 404);
        }

        // Browser-controlled return only reports; IPN/query alone can allocate money.
        return ApiResponse::data(['verified' => true, 'order_code' => $order->order_code, 'result' => $verified->state, 'payment_status' => $order->payment_status]);
    }
}
