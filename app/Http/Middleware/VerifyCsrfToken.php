<?php

namespace App\Http\Middleware;

use Closure;
use Response;
use App\Trace\Trace;
use App\Trace\TraceCode;
use GraphQL\Language\Parser;
use Illuminate\Http\Request;
use Razorpay\Api\Errors\ErrorCode;
use Razorpay\Api\Errors\BadRequestError;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{

    /**
     * Constants
     */
    const OPERATION_NAME                     = 'operationName';
    const ORGANISATION_INFORMATION           = 'organisationInformation';
    const ORGANISATION_INFORMATION_BY_DOMAIN = 'organisationInformationByDomain';

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
        '/user/register_unbounce',
        '/user/salesforce_event',
        '/user/salesforce/otp',
        '/user/salesforce/otp/verify',

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
        '/v1/growth/assets',
        '/admin/saml/callback',
        '/internal/session'
    ];

    /**
     * Handle an incoming request.
     * shouldPassThrough and inExceptArray is same
     *
     * @param Request $request
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

            try
            {
                if (isset($input['operations']) === true)
                {
                    $operations = json_decode($input['operations'], true);

                    $queryData = Parser::parse($operations['query']);
                }
                else if (isset($input['query']) === true)
                {
                    $queryData = Parser::parse($input['query']);
                }
            }
            catch (\Throwable $e)
            {
                app('trace')->info(TraceCode::ERROR_PARSING_GRAPH_PAYLOAD_DATA, [
                    'context' => $e,
                ]);

                throw new BadRequestError('Invalid Payload', ErrorCode::BAD_REQUEST_ERROR, 400);
            }

            // If the graph query is to seek org information
            // skip CSRF token check
            if ((isset($queryData) === true) and
                (($this->isOperationName(self::ORGANISATION_INFORMATION, $queryData) === true) or
                 ($this->isOperationName(self::ORGANISATION_INFORMATION_BY_DOMAIN, $queryData) === true)))
            {
                return $next($request);
            }
            else
            {
                if (
                    $this->isReading($request) or
                    $this->runningUnitTests() or
                    $this->inExceptArray($request) or
                    $this->tokensMatch($request)
                )
                {
                    if(empty($request->input('_request_identifier')) === false)
                    {
                        $this->logTokensMatch($request);
                    }

                    return $next($request);
                }
                else
                {
                    return Response::json(
                        $this->getErrorResponseForGraphQlClients($request));
                }
            }
        }
        else
        {
            if (
                $this->isReading($request) ||
                $this->runningUnitTests() ||
                $this->inExceptArray($request) ||
                $this->tokensMatch($request) ||
                $this->tokensMatchCookie($request)
            )
            {
                return $this->addCookieToResponse($request, $next($request));
            }

            throw new TokenMismatchException('CSRF token mismatch.',400);
        }
    }

    private function isOperationName($operationName, $queryData)
    {
        $definitions = json_decode($queryData)->definitions;

        foreach($definitions as $definition)
        {
            $definitionKind = $definition->kind;

            if ($definitionKind === "OperationDefinition")
            {
                $selectionSet = $definition->selectionSet;
                $selections = $selectionSet->selections;

                foreach($selections as $selection)
                {
                    $selectorName = $selection->name->value;

                    if ($selectorName === $operationName)
                    {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function getErrorResponseForGraphQlClients($request)
    {
        $baseAppUrl = config('app.url');

        app('trace')->info(TraceCode::UNAUTHORISED_BACKTRACE, [
            'backtrace'                  => debug_backtrace(10),
            'token_match_cookie_result'  => $this->tokensMatchCookie($request),
            'tokens_match_result'        => $this->tokensMatch($request),
            'hash_equals_result'         => $this->logTokensMatch($request),
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
     * @param Request $request
     *
     * @return bool
     */
    private function tokensMatchCookie($request): bool
    {
        $sessionToken = $request->session()->token();

        $token = $this->getTokenFromRequest($request);

        $xsrfCookieToken = $request->cookie('XSRF-TOKEN');

        app('trace')->info(TraceCode::MISMATCHED_VERIFY_TOKEN, [ // nosemgrep : php.lang.security.weak-crypto.weak-crypto
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
            hash_equals($xsrfCookieToken, $token) === true) {

            return true;
        }

        return false;
    }

    private function logTokensMatch($request)
    {
        $sessionToken = $request->session()->token();

        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        if (! $token && $header = $request->header('X-XSRF-TOKEN')) {
            $token = $this->encrypter->decrypt($header);
        }

        if (! is_string($sessionToken) || ! is_string($token)) {
            return false;
        }

        app('trace')->info(TraceCode::TOKEN_MATCH_TRACE, [ // nosemgrep : php.lang.security.weak-crypto.weak-crypto
            'session_token'      => md5($sessionToken ?? ''),
            '_token'             => md5($request->input('_token') ?? ''),
            'x_csrf_token'       => md5($request->header('X-CSRF-TOKEN') ?? ''),
            'x_xsrf_token'       => md5($request->header('X-XSRF-TOKEN') ?? ''),
            'token'              => md5($token ?? ''),
            'hash_equals_result' => hash_equals($sessionToken, $token),
            '_request_identifier'=> $request->input('_request_identifier') ?? '',
            'xsrf_token_cookie'  => md5($request->cookie('XSRF-TOKEN') ?? '')
        ]);

        return hash_equals($sessionToken, $token);
    }

}
