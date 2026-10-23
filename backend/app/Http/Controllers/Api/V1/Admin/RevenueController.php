<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RevenueRequest;
use App\Services\RevenueService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class RevenueController extends Controller
{
    public function index(RevenueRequest $request, RevenueService $revenue): JsonResponse
    {
        return ApiResponse::data($revenue->report($request->validated('from'), $request->validated('to'), $request->validated('granularity', 'day')));
    }
}
