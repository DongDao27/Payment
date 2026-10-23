<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscribeNewsletterRequest;
use App\Models\NewsletterSubscription;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class NewsletterController extends Controller
{
    public function __invoke(SubscribeNewsletterRequest $request): JsonResponse
    {
        // A unique key also makes concurrent submissions converge on one row.
        NewsletterSubscription::firstOrCreate(
            ['email' => $request->validated('email')],
            ['consented_at' => now()],
        );

        return ApiResponse::data(['message' => 'Đã ghi nhận đăng ký nhận bản tin.'], 202);
    }
}
