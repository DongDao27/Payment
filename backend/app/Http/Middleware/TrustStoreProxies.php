<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

class TrustStoreProxies extends TrustProxies
{
    protected function proxies()
    {
        return config('store.trusted_proxies', []);
    }

    protected function setTrustedProxyIpAddresses(Request $request)
    {
        if ($this->proxies() !== []) {
            parent::setTrustedProxyIpAddresses($request);
        }
    }
}
