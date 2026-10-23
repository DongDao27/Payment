<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = (string) Str::uuid();
        $request->attributes->set('request_id', $id);
        Log::withContext(['request_id' => $id]);
        $response = $next($request);
        $response->headers->set('X-Request-ID', $id);
        if ($request->is('api/*', 'sanctum/*') && ! ($request->isMethod('GET') && $request->is('api/v1/storefront') && $response->isSuccessful())) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
