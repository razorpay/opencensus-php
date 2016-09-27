<?php
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
        Symfony\Component\HttpFoundation\Request::HEADER_CLIENT_IP => 'X_FORWARDED_FOR',
        Symfony\Component\HttpFoundation\Request::HEADER_CLIENT_PROTO => 'X_FORWARDED_PROTO',
        Symfony\Component\HttpFoundation\Request::HEADER_CLIENT_PORT => 'X_FORWARDED_PORT',
    ]
];