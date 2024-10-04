<?php

use App\Http\Middleware\TrustedProxy;

return [
    /*
     * Set trusted proxy IP addresses.
     *
     * Both IPv4 and IPv6 addresses are
     * supported, along with CIDR notation.
     *
     * The "*" character is syntactic sugar
     * within TrustedProxy to trust any proxy;
     * a requirement when you cannot know the address
     * of your proxy (e.g. if using Rackspace balancers).
     */
    'proxies' => [
        '10.0.0.0/16',
        '10.1.0.0/16',
        '10.2.0.0/16',
        '10.24.0.0/16',
        '10.25.0.0/16',
        '10.26.0.0/16',
        '10.21.0.0/16',
        '10.80.0.0/16',
        '10.64.0.0/16',
        '10.65.0.0/16',
        '10.81.0.0/16',
        '10.66.0.0/16',
        '10.67.0.0/16'
    ],
    /*
     * Default Header Names
     *
     * Change these if the proxy does
     * not send the default header names.
     *
     * Note that headers such as X-Forwarded-For
     * are transformed to HTTP_X_FORWARDED_FOR format.
     *
     * The following are Symfony defaults, found in
     * \Symfony\Component\HttpFoundation\Request::$trustedHeaders
     */
    'headers' => [
        TrustedProxy::HEADER_CLIENT_IP => 'X_FORWARDED_FOR',
        TrustedProxy::HEADER_CLIENT_PROTO => 'X_FORWARDED_PROTO',
        TrustedProxy::HEADER_CLIENT_PORT => 'X_FORWARDED_PORT',
        Symfony\Component\HttpFoundation\Request::HEADER_FORWARDED => null,
    ]
];
