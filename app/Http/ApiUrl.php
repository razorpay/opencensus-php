<?php

namespace App\Http;

use Config;
use Request;

class ApiUrl
{
    const API_HOST_COOKIE_KEY = 'rzp_api_host';

    /**
     * Whitelist of API hosts that can be sent in the `rzp_api_host` cookie
     * @var array
     */
    const ALLOWED_API_HOSTS = [
        'dev'        => '*',
        'dev_docker' => '*',
        'beta'       => [
            'https://beta-api.razorpay.com/v1/',
            'https://beta-api-canary.razorpay.com/v1/',
            'https://beta-api.razorpay.in/v1/',
            'https://beta-api-canary.razorpay.in/v1/',
        ],
        'charlie'    => [
            'https://charlie-api.razorpay.com/v1/',
            'https://charlie-api.razorpay.in/v1/',
        ],
        'delta'      => [
            'https://delta-api.razorpay.com/v1/',
            'https://delta-api.razorpay.in/v1/',
        ],
        'echo'       => [
            'https://echo-api.razorpay.com/v1/',
            'https://echo-api.razorpay.in/v1/',
        ],
        'production' => [
            'https://api.razorpay.com/v1/',
            'https://prod-api-canary.razorpay.com/v1/',
            'https://api-dark.razorpay.com/v1/',
        ],
    ];

    public static function getApiBaseUrl()
    {
        $url = Config::get('api.url');

        $hostCookie = $_COOKIE[self::API_HOST_COOKIE_KEY] ?? null;

        if (self::isValidApiHostCookie($hostCookie) === true)
        {
            $url = $hostCookie;
        }

        return $url;
    }

    public static function isValidApiHostCookie(string $cookie = null)
    {
        if (empty($cookie) === true)
        {
            return false;
        }

        $env = \App::environment();

        $allowedHosts = self::ALLOWED_API_HOSTS[$env] ?? [];

        return (($allowedHosts === '*') or
            (in_array($cookie, $allowedHosts, true) === true));
    }
}
