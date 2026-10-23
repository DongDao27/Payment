<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class BusinessException extends RuntimeException implements ShouldntReport
{
    public function __construct(public readonly string $errorCode, string $message, public readonly int $status = 409)
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error($this->errorCode, $this->getMessage(), $this->status);
    }
}
