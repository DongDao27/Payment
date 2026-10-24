<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Services\PasswordRecoveryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PasswordRecoveryController extends Controller
{
    public function requestLink(ForgotPasswordRequest $request, PasswordRecoveryService $service): JsonResponse
    {
        if (app()->environment('production') && in_array(config('mail.default'), ['log', 'array'], true)) {
            throw new BusinessException('RECOVERY_UNAVAILABLE', 'Khôi phục mật khẩu tạm thời chưa khả dụng. Vui lòng liên hệ nhà sách.', 503);
        }
        $service->requestLink($request->validated('email'));

        return ApiResponse::data(['message' => 'Nếu email khớp tài khoản khách hàng, bạn sẽ nhận được liên kết đặt lại mật khẩu. Vui lòng kiểm tra cả thư rác.'], 202);
    }

    public function reset(ResetPasswordRequest $request, PasswordRecoveryService $service): JsonResponse
    {
        $service->reset($request->validated());
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return ApiResponse::data(['message' => 'Mật khẩu đã được cập nhật. Vui lòng đăng nhập lại.']);
    }
}
