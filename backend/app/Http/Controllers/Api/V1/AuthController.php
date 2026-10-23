<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\NormalizeIdentity;
use App\Actions\Auth\RegisterCustomer;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterCustomer $register): JsonResponse
    {
        $user = $register->handle($request->validated());
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return ApiResponse::data(new UserResource($user->refresh()), 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $identifier = $request->validated('identifier');
        $field = str_contains($identifier, '@') ? 'email' : 'phone';
        $normalized = $field === 'email' ? NormalizeIdentity::email($identifier) : NormalizeIdentity::phone($identifier);
        if (! Auth::guard('web')->attempt([$field => $normalized, 'password' => $request->validated('password')])) {
            throw ValidationException::withMessages(['identifier' => 'The credentials are invalid.']);
        }
        $request->session()->regenerate();

        return ApiResponse::data(new UserResource($request->user()));
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::data(new UserResource($request->user()));
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
