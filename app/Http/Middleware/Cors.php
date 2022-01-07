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

    /*
     * url_config   - fetching the actual host from env
     * routes       - if routes are present then CORS is enabled for only those routes
     */
    const CORS_CONFIG = [
        'auth_domain'       => [
            'url_config'    => 'oauth.auth_service_url',
            'routes'        => [
                '/user/session'
            ]
        ],

        'banking_domain'    => [
            'url_config'    => 'app.banking_service_url',
        ],

        'docs_domain'       => [
            'url_config'    => 'app.docs_url'
        ],

        'rzp_website_domain'=> [
            'url_config'    => 'app.rzp_website_url'
        ],

        'next_rzp_domain'   => [
            'url_config'    => 'app.next_rzp_url'
        ],

        'static_web_domain' => [
            'url_config'    => 'app.static_web_url'
        ]
    ];

    protected function shouldAllowCors($request, $originHost) : bool
    {
        $env = \App::environment();

        if(($env === 'stage') and ($this->isDevstackHost($originHost) === true))
        {
            return true;
        }

        foreach (self::CORS_CONFIG as $key => $config)
        {
            $url_config = $config['url_config'];

            $url = parse_url(config($url_config), PHP_URL_HOST);

            if ($originHost === $url)
            {
                $routes = $config['routes'] ?? null;

                if ($routes === null)
                {
                    return true;
                }

                if(in_array($request->getPathInfo(), $routes, true) === true)
                {
                    return true;
                }
            }
        }
        return false;
    }

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

        if ($this->shouldAllowCors($request, $originHost) === true)
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
                'x-signup-flow-v2',
                'x-xsrf-token'
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
