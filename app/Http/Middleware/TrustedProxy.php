<?php

namespace RZP\Http\Middleware;

use Illuminate\Http\Request;
use Fideloper\Proxy\TrustProxies as Middleware;

class TrustedProxy extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array
     */
    protected $proxies = [
        '10.0.0.0/8', // For remote proxy.
        '127.0.0.1',  // For local openresty container acting as proxy.
    ];

    /**
     * The current proxy header mappings.
     *
     * @var null|string|int
     */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}
