<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\ApiUrl;
use App\Http\Headers;

class Cors
{
    protected $authRoutes = [
        '/user/session',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $originDomain = ApiUrl::getRequestOriginUrl();

        $originHost = parse_url($originDomain, PHP_URL_HOST);

        $crossOriginDomains = [
            'auth'       => parse_url(config('oauth.auth_service_url'), PHP_URL_HOST),
            'banking'    => parse_url(config('app.banking_service_url'), PHP_URL_HOST),
            'auth_cde'   => parse_url(config('oauth.auth_service_url_cde'), PHP_URL_HOST),
            'docs'       => parse_url(config('app.docs_url'), PHP_URL_HOST),
        ];

        $crossOriginPolicy = false;

        $env = \App::environment();

        if (($originHost === $crossOriginDomains['banking']) or
            ($originHost === $crossOriginDomains['docs']) or
            (($originHost === $crossOriginDomains['auth']) and
                (in_array($request->getPathInfo(), $this->authRoutes, true) === true)) or
            (($env === 'stage') and ($this->isDevstackHost($originHost) === true)))
        {
            // For Auth Origin we have to enable cors only for one route.
            $crossOriginPolicy = true;
        }

        if ($crossOriginPolicy === true)
        {
            $allowHeaders = [
                'X-Requested-With',
                Headers::CSRF_TOKEN,
                'Content-Type',
                'X-Report-Type',
                'x-recaptcha-mode',
                // Added this to allow email verification via OTP in X
                'x-send-email-otp',
                // Added to allow access to users api for non confirmed user
                'x-signup-flow-v2'
            ];

            $headers = [
                'Access-Control-Allow-Origin'       => $originDomain,
                'Access-Control-Allow-Methods'      => 'POST, GET, OPTIONS, PATCH, PUT, DELETE',
                'Access-Control-Allow-Credentials'  => 'true',
                'Access-Control-Allow-Headers'      => implode(',', $allowHeaders),
                'Access-Control-Expose-Headers'     => Headers::CSRF_TOKEN,
            ];

            //
            // For an OPTIONS pre-flight request, simply return a 200
            // with the above headers
            //
            if ($request->getMethod() === 'OPTIONS')
            {
                return \Response::json([], 200, $headers);
            }

            $response = $next($request);

            //
            // For GET/POST requests, add CORS headers before sending
            // the response
            //
            $response->withHeaders($headers);

            return $response;
        }

        return $next($request);
    }

    /**
     * Enable CORS policy for Devstack hosts *.dev.razorpay.in
     *
     * @return boolean
     */
    private function isDevstackHost($originHost) : bool
    {
        preg_match('/\.dev\.razorpay\.in$/', $originHost, $matches);

        return (isset($matches[0]));
    }
}
