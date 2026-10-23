<?php

use App\Http\Middleware\RequestContext;
use App\Http\Middleware\RequireRole;
use App\Http\Middleware\TrustStoreProxies;
use App\Support\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(RequestContext::class);
        $middleware->replace(TrustProxies::class, TrustStoreProxies::class);
        $middleware->alias(['role' => RequireRole::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());
        $exceptions->respond(function (Response $response) {
            if (! request()->is('api/*') || $response->getStatusCode() < 400) {
                return $response;
            }
            $status = $response->getStatusCode();
            $body = json_decode($response->getContent(), true) ?? [];
            if (isset($body['error'])) {
                return $response;
            }
            $codes = [401 => 'UNAUTHENTICATED', 403 => 'FORBIDDEN', 404 => 'NOT_FOUND', 405 => 'METHOD_NOT_ALLOWED', 409 => 'CONFLICT', 419 => 'CSRF_TOKEN_MISMATCH', 422 => 'VALIDATION_ERROR', 429 => 'TOO_MANY_REQUESTS', 503 => 'SERVICE_UNAVAILABLE'];
            $result = ApiResponse::error($codes[$status] ?? 'SERVER_ERROR', $status >= 500 ? 'The service is temporarily unavailable.' : ($body['message'] ?? Response::$statusTexts[$status] ?? 'Request failed.'), $status, $body['errors'] ?? []);
            foreach (['Retry-After', 'X-RateLimit-Limit', 'X-RateLimit-Remaining'] as $header) {
                if ($response->headers->has($header)) {
                    $result->headers->set($header, $response->headers->get($header));
                }
            }

            return $result;
        });
    })->create();
