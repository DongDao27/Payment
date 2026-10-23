<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    public function test_client_ip_is_forwarded_only_from_configured_proxy_network(): void
    {
        Route::get('/api/v1/_proxy-check', fn (Request $request) => response()->json(['ip' => $request->ip(), 'secure' => $request->isSecure()]));
        config(['store.trusted_proxies' => ['172.28.0.0/16']]);
        $this->withServerVariables(['REMOTE_ADDR' => '172.28.0.5'])->withHeaders(['X-Forwarded-For' => '203.0.113.17', 'X-Forwarded-Proto' => 'https'])->getJson('/api/v1/_proxy-check')->assertJsonPath('ip', '203.0.113.17')->assertJsonPath('secure', true);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.7'])->getJson('http://localhost/api/v1/_proxy-check')->assertJsonPath('ip', '192.0.2.7')->assertJsonPath('secure', false);
    }

    public function test_empty_proxy_config_does_not_trust_spoofed_forwarded_headers(): void
    {
        config(['store.trusted_proxies' => []]);
        Route::get('/api/v1/_proxy-check', fn (Request $request) => response()->json(['ip' => $request->ip()]));
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.8'])->withHeader('X-Forwarded-For', '203.0.113.99')->getJson('/api/v1/_proxy-check')->assertJsonPath('ip', '192.0.2.8');
    }
}
