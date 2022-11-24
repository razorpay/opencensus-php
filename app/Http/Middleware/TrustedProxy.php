<?php

namespace RZP\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Symfony\Component\HttpFoundation\Request as RequestAlias;

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
    protected $headers = RequestAlias::HEADER_FORWARDED           |
                         RequestAlias::HEADER_X_FORWARDED_FOR     |
                         RequestAlias::HEADER_X_FORWARDED_HOST    |
                         RequestAlias::HEADER_X_FORWARDED_PORT    |
                         RequestAlias::HEADER_X_FORWARDED_PROTO   |
                         RequestAlias::HEADER_X_FORWARDED_AWS_ELB |
                         RequestAlias::HEADER_X_FORWARDED_TRAEFIK |
                         RequestAlias::HEADER_X_FORWARDED_PREFIX;
}
