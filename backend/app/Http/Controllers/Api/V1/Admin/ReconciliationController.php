<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveCaseRequest;
use App\Http\Resources\ReconciliationResource;
use App\Models\ReconciliationCase;
use App\Services\ReconciliationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReconciliationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['page' => ['sometimes', 'integer', 'min:1'], 'per_page' => ['sometimes', 'integer', 'between:1,100'], 'status' => ['sometimes', 'in:open,resolved']]);
        $query = ReconciliationCase::with('order');
        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }

        return ApiResponse::page($query->orderByDesc('id')->paginate($data['per_page'] ?? 20), ReconciliationResource::class);
    }

    public function update(ResolveCaseRequest $request, ReconciliationCase $case, ReconciliationService $reconciliation): JsonResponse
    {
        return ApiResponse::data(new ReconciliationResource($reconciliation->resolve($case, $request->validated(), $request->user()->id)->load('order')));
    }
}
