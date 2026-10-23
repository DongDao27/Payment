<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class ApiResponse
{
    public static function meta(array $extra = []): array
    {
        return array_merge([
            'server_time' => now()->utc()->toISOString(),
            'request_id' => request()->attributes->get('request_id') ?? (string) Str::uuid(),
        ], $extra);
    }

    public static function data(mixed $data, int $status = 200, array $meta = []): JsonResponse
    {
        return response()->json(['data' => $data, 'meta' => self::meta($meta)], $status);
    }

    public static function error(string $code, string $message, int $status, array $fields = []): JsonResponse
    {
        return response()->json([
            'error' => ['code' => $code, 'message' => $message, 'fields' => (object) $fields],
            'meta' => self::meta(),
        ], $status);
    }

    public static function page(LengthAwarePaginator $page, string $resource): JsonResponse
    {
        return self::data($resource::collection($page->getCollection()), meta: [
            'pagination' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()],
        ]);
    }
}
