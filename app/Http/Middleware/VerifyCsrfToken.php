<?php

namespace App\Http\Middleware;

use Closure;
use Response;
use App\Trace\Trace;
use App\Trace\TraceCode;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{

    /**
     * Constants
     */
    const OPERATION_NAME            = 'operationName';
    const ORGANISATION_INFORMATION  = 'organisationInformation';

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        // This is posted from razorpay.com
        '/contact',

        // This is posted from Slack
        '/slack',

        // Posted from API
        '/test/transactions/*',
        '/live/transactions/*',

        // Register Route
        '/user/register',

        // Aggregation requests
        '/test/analytics/aggregations/day',
        '/test/analytics/aggregations/week',
        '/test/analytics/aggregations/month',
        '/test/analytics/aggregations/year',
        '/test/analytics/payment/aggregations',

        '/live/analytics/aggregations/day',
        '/live/analytics/aggregations/week',
        '/live/analytics/aggregations/month',
        '/live/analytics/aggregations/year',
        '/live/analytics/payment/aggregations',
    ];

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     * @throws \Illuminate\Session\TokenMismatchException
     */
	public function handle($request, Closure $next)
	{
        $routeName = $request->route()->getName();

        if ($routeName === 'graph_request')
        {

            $input = $request->input();

            // If the graph query is to seek org information
            // skip CSRF token check
            if(isset($input[self::OPERATION_NAME]) and
                $input[self::OPERATION_NAME] === self::ORGANISATION_INFORMATION)
            {
                return $next($request);
            }
            else
            {   
                if (
                    $this->isReading($request) or
                    $this->runningUnitTests() or
                    $this->shouldPassThrough($request) or
                    $this->tokensMatch($request)
                )
                {
                    return $next($request);
                }
                else
                {
                    return Response::json(
                        $this->getErrorResponseForGraphQlClients());
                }
            }
        }
        else
        {
            if (
                $this->isReading($request) ||
                $this->runningUnitTests() ||
                $this->shouldPassThrough($request) ||
                $this->tokensMatch($request) ||
                $this->tokensMatchCookie($request)
            )
            {
                return $this->addCookieToResponse($request, $next($request));
            }

            throw new TokenMismatchException;
        }
    }

    private function getErrorResponseForGraphQlClients()
    {
        $baseAppUrl = config('app.url');
        
        app('trace')->info(TraceCode::UNAUTHORISED_BACKTRACE, [
            'backtrace' => debug_backtrace(10),
        ]);
        
        return [
            'errors'    => [
                [
                    'message'   => 'Unauthorized',
                    'extensions'    => [
                        'code'          => 'UNAUTHENTICATED',
                        'url'           => $baseAppUrl.'/user/signin',
                        'status'        => 401,
                        'statusText'    => 'Unauthorized',
                    ]
                ]
            ],

            'data'      => null,
        ];
    }

    /**
     * Here we match with the incoming cookie,
     *
     * This is a valid csrf implementation because we get the data from header and match it with the token which we
     * receive
     * Laravel session tokens are wacky in the implementation of csrf when concurrent requests are there.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    private function tokensMatchCookie($request)
    {
        $sessionToken = $request->session()->token();

        $token = $request->header('X-CSRF-TOKEN');

        if (empty($token) == true && $header = $request->header('X-XSRF-TOKEN')) {
            $token = $this->encrypter->decrypt($header);
        }

        $xsrfCookieToken = $request->cookie('XSRF-TOKEN');

        app('trace')->info(TraceCode::MISMATCHED_VERIFY_TOKEN, [
            'session_token' => md5($sessionToken ?? ''),
            'verify_token'  => md5($token ?? ''),
            'xsrf_token'    => md5($xsrfCookieToken ?? ''),
        ]);

        //
        // If any one of required tokens are not sent then return false
        // this is to avoid validating requests when
        // XSRF was not sent in both header and cookie
        //
        if ((empty($xsrfCookieToken) === true) or
            (empty($token) === true))
        {
            return false;
        }

        if ((is_string($xsrfCookieToken) === true) and (is_string($token) === true) and
            hash_equals($xsrfCookieToken, $token) == true) {

            return true;
        }

        return false;
    }
}
