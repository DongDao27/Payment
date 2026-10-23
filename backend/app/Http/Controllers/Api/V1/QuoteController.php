<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteRequest;
use App\Http\Resources\QuoteResource;
use App\Services\QuoteService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class QuoteController extends Controller
{
    public function store(QuoteRequest $request, QuoteService $quotes): JsonResponse
    {
        return ApiResponse::data(new QuoteResource($quotes->create($request->user(), $request->validated())), 201);
    }
}
