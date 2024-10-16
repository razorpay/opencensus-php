<?php

namespace App\Http;
use Session;
use Config;
use Request;
use App\Trace\TraceCode;

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
            'https://beta-api.razorpay.in/v1/',
            'https://beta-api-canary.razorpay.in/v1/',
        ],

        'charlie'    => [
            'https://charlie-api.razorpay.in/v1/',
        ],
        'delta'      => [
            'https://delta-api.razorpay.in/v1/',
        ],
        'echo'       => [
            'https://echo-api.razorpay.in/v1/',
        ],
        'production' => [
            'https://api-canary.razorpay.com/v1/',
            'https://api-dark.razorpay.com/v1/',
            'https://api-dark-concierge.razorpay.com/v1/',
            'https://k8s-prod-api.razorpay.com/v1/',
            'https://api-kong.razorpay.com/v1/',
            'https://api-whatsapp.razopay.com/v1/',
            'https://api-merchant-proxy.razorpay.com./v1/',  // for testing
        ],
    ];
    public static function getApiHost()
    {
        $url = Config::get('api.url');
        $hostCookie = $_COOKIE[self::API_HOST_COOKIE_KEY] ?? null;

        if (self::isValidApiHostCookie($hostCookie) === true)
        {
            $url = $hostCookie;
        }
        return parse_url($url,PHP_URL_HOST);
    }
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

    public static function getCheckoutApi()
    {
        $url = Config::get('api.checkout_url');
        if (!$url)
        {
            $url = self::getApiBaseUrl();
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

    public static function getRequestOrigin()
    {
        $source = self::getRequestOriginUrl();

        return parse_url($source, PHP_URL_HOST) ?? 'unknown_origin';
    }

    public static function getRequestOriginUrl()
    {
        // Since client is loading the app in iframe we will not get the request with correct product so client is
        // sending extra header.
        $originDomain = Request::server('HTTP_X_ORIGIN_PRODUCT');

        if (empty($originDomain) === true)
        {
            // Fallback for origin is referrer.
            // OSWAP suggests to use referrer if origin header is not present.
            // https://www.owasp.org/index.php/Cross-Site_Request_Forgery_(CSRF)_Prevention_Cheat_Sheet#Identifying_Source_Origin_.28via_Origin.2FReferer_header.29
            $originDomain = Request::server('HTTP_ORIGIN') ?? Request::server('HTTP_REFERER');
        }

        return $originDomain;
    }

    public static function isBankingOriginRequest($shouldUseBankingOriginRequestV2 = false)
    {
        $originDomain = self::getRequestOriginUrl();
        $originHost = parse_url($originDomain, PHP_URL_HOST);

        $requestDomain = Request::url();
        $requestHost = parse_url($requestDomain, PHP_URL_HOST);

        if ($shouldUseBankingOriginRequestV2 === true)
        {
            $bankingUrls = explode(',', config('app.banking_service_url_v2'));

            foreach($bankingUrls as $bankingUrl) {
                $bankingHost = parse_url(trim($bankingUrl), PHP_URL_HOST);
                if ($originHost === $bankingHost || $requestHost === $bankingHost) {
                    return true;
                }
            }
        }

        $bankingHost = parse_url(config('app.banking_service_url'), PHP_URL_HOST);

        $bankLmsBankingHost = parse_url(config('app.bank_lms_banking_service_url'), PHP_URL_HOST);

        return ($originHost === $bankingHost or $originHost === $bankLmsBankingHost);
    }

    public static function isPrimaryOriginRequest()
    {
        $originDomain = self::getRequestOriginUrl();

        $originHost = parse_url($originDomain, PHP_URL_HOST);

        $primaryHost = parse_url(config('app.url'), PHP_URL_HOST);

        return ($originHost === $primaryHost);
    }
}
