<?php

namespace App\Http\Middleware;

use Trace;
use Closure;
use App\Http\ApiUrl;
use App\Http\Headers;
use App\Trace\TraceCode;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        'hosted_domain'       => [
            'url_config'    => 'app.hosted_service_url',
            'routes'        => [
                '/user/session'
            ]
        ],

        'banking_domain'    => [
            'url_config'    => 'app.banking_service_url',
        ],

        'bank_lms_banking_domain'    => [
            'url_config'    => 'app.bank_lms_banking_service_url',
        ],

        'campaignhq_domain'    => [
            'url_config'    => 'app.campaignhq_url',
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
        ],

        'easy_dashboard_domain' => [
            'url_config'    => 'app.easy_dashboard_url'
        ],
        'easy_curlec_signup_domain' => [
            'url_config'    => 'app.easy_curlec_signup_url'
        ],

        'curlec_accounts_domain' => [
            'url_config'    => 'app.curlec_accounts_url'
        ],

        'razorpay_accounts_domain' => [
            'url_config'    => 'app.razorpay_accounts_url'
        ],

        'axis_unipg_accounts_domain' => [
            'url_config'    => 'app.axis_unipg_accounts_url'
        ],

        'axis_easypay_accounts_domain' => [
            'url_config'    => 'app.axis_easypay_accounts_url'
        ],

        'hdfc_pro_accounts_domain' => [
            'url_config'    => 'app.hdfc_pro_accounts_url'
        ],

        'hdfc_giga_accounts_domain' => [
            'url_config'    => 'app.hdfc_giga_accounts_url'
        ],

        'hdfc_vas_accounts_domain' => [
            'url_config'    => 'app.hdfc_vas_accounts_url'
        ],

        'hdfc_collect_now_accounts_domain' => [
            'url_config'    => 'app.hdfc_collect_now_accounts_url'
        ],

        'yesbank_accounts_domain' => [
            'url_config'    => 'app.yesbank_accounts_url'
        ],

        'idfcbank_accounts_domain' => [
            'url_config'    => 'app.idfcbank_accounts_url'
        ],

        'indusindbank_accounts_domain' => [
            'url_config'    => 'app.indusindbank_accounts_url'
        ],
    ];

    protected function shouldAllowCors($request, $originHost) : bool
    {
        $env = \App::environment();

        if (($env === 'stage') or ($env === 'beta'))
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
                'X-Product-Type',
                'X-Product',
                'x-recaptcha-mode',
                // Added this to allow email verification via OTP in X
                'x-send-email-otp',
                // Added to allow access to users api for non confirmed user
                'x-signup-flow-v2',
                'x-xsrf-token',
                'apollographql-client-name',
                'x-app-mode',
                'x-org-id',
                'x-dashboard-merchant-id',
                'x-dashboard-user-id',
                'x-razorpay-account',
                'x-razorpay-user-merchant-region',
                // x-partner-* headers contain meta data used during phantom signup
                Headers::X_PARTNER_APPLICATION_ID,
                Headers::X_PARTNER_OAUTH_REFERRAL,
                Headers::ONBOARDING_SIGNATURE,
                'request-start-time',
                'sentry-trace',
                'Authorization',
                'baggage',
                Headers::X_PAYOUT_IDEMPOTENCY,
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

            if($response instanceof StreamedResponse)
            {
                foreach ($headers as $key => $value)
                {
                    $response->headers->set($key, $value);
                }

                $response->headers->set('X-Accel-Buffering', 'no');

                Trace::info(TraceCode::CHUNKED_DETAILS, [
                    'location' => 'cors_should_allow_cors',
                ]);

                return $response;
            }

            $response->withHeaders($headers);

            return $response;
        }

        $response = $next($request);

        if($response instanceof StreamedResponse)
        {
            $response->headers->set('X-Accel-Buffering', 'no');

            Trace::info(TraceCode::CHUNKED_DETAILS, [
                'location' => 'cors_should_not_allow_cors',
            ]);
        }

        return $response;
    }
}
