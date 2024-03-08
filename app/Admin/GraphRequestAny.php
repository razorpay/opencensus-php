<?php

namespace App\Admin;

use App\User;
use Auth;
use Config;
use Request;
use Session;
use SplFileInfo;
use App\Trace\TraceCode;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\RequestException;

use App\Http\ApiUrl;
use App\Http\Headers;

class GraphRequestAny
{
    const COOKIE_HEADER = 'Set-Cookie';
    const CONTENT_TYPE_JSON = 'application/json';
    const CONTENT_TYPE_MULTIPART = 'multipart/form-data';
    const CONTENT_TYPE_FORM = 'application/x-www-form-urlencoded';

    const SERVER_ERROR       = 'Internal Server Error';

    // true is a dummy value
    // presence any value even false will make the key whitelisted
    // For more details please refer working of array_intersect_key herew
    // https://www.php.net/manual/en/function.array-intersect-key.php
    const WHITELISTED_HEADERS_FOR_RESPONSE = [
        'Set-Cookie'                => true,
        'X-Csrf-Token'              => true,
        'X-Dashboard-Merchant-Id'   => true,
        'X-Dashboard-User-Id'       => true,
        'X-App-Mode'                => true,
        'X-Org-Id'                  => true,
        'X-Mobile-Access-Token'     => true,
        'X-Mobile-Client-Id'        => true,
        'X-Mobile-Refresh-Token'    => true,
        'Access-Token-2fa'          => true,
    ];

    protected $request;

    protected $data;

    protected $headers;

    private static function getWhitelistedHeaders(array $allHeaders): array
    {
        return array_intersect_key(
            array_change_key_case($allHeaders),
            array_change_key_case(self::WHITELISTED_HEADERS_FOR_RESPONSE)
        );
    }

    function __construct(array $data)
    {
        $app = \App::getFacadeRoot();

        $graphQlServerUrl = Config::get('razorpay.graphql.server_url');

        $this->request = new Guzzle([
            'base_uri'  => $graphQlServerUrl,
        ]);

        $this->data = $data;

        $this->trace = $app['trace'];

        $this->processHeaders();
    }

    public function send()
    {
        $options = [
            'headers'       => $this->headers,
        ];

        $options = array_merge($options, $this->getDataForOutgoingRequest($this->data));
        $spanOptions = (new ApiRequestSpan($this->request))::getRequestSpanOptions(Config::get('razorpay.graphql.server_url'));
        $graphQlPath = Config::get('razorpay.graphql.server_path');

        try
        {
            $response = (new ApiRequestSpan($this->request))->wrapRequestInSpan(
                'POST',
                $graphQlPath,
                [
                    'options' => $options,
                    'headers' => $options['headers'],
                ],
                $spanOptions
            );

            $headersToBeAppended = $this->getWhitelistedHeaders($response->getheaders());

            $response = json_decode($response->getBody(), true);

            return [$response, $headersToBeAppended];

        }
        catch(RequestException $exception)
        {
            $errors = [$exception->getMessage()];

            /*
             * commenting this as this is failing due to null value returned instead of array of headers
             *
            return [$errors, null];
            */

            // since X-mobile app has integrated internal server error for this case hence returning the same
            // To do : once X-mobile app handles this then return correct error message
            // slack thread : https://razorpay.slack.com/archives/CQ56RK941/p1641467424016100?thread_ts=1641445389.002600&cid=CQ56RK941

            $data = [
                'success' => false,
                'errors'  => [self::SERVER_ERROR]
            ];

            return [$data, []];
        }
        catch(\Exception $exception)
        {
            $error = $this->getErrorResponse($exception->getMessage());

            return [$error, []];
        }
    }

    private function processHeaders()
    {
        $this->headers = [];

        $this->addDefaultHeaders();

        $this->addProxyAuthHeadersIfUserLoggedIn();
    }

    private function addDefaultHeaders()
    {
        $domain = \Request::server('SERVER_NAME');

        $originDomain = ApiUrl::getRequestOriginUrl();

        $requestedClientIPS = \Request::ips();

        $clientIp = end($requestedClientIPS);

        $ipAddress = Request::ip();

        $userAgent = Request::header('User-Agent');

        $csrfToken = Request::header('X-Csrf-Token');

        $cookies = Request::header('Cookie');

        $orgId = Request::header('X-Org-Id');

        $apolloClientName = Request::header('apollographql-client-name');

        $apolloClientVersion = Request::header('apollographql-client-version');

        $appMode = Request::header('x-app-mode');

        $productType = Request::header('X-Product-Type');

        $devServeHeader = Request::header('rzpctx-dev-serve-user');

        $mobileDebugId = Request::header('x-mobile-debug-id');  // adding a unique key with value = ({userId}:{uniqueDeviceId}) to help in debugging issues for multiple platforms. This header will not be available for web applications.

        $rzpAccount = Request::header('x-razorpay-account');

        $defaultHeaders =  [
            'X-Dashboard'                           => 'true',
            'X-Org-Hostname'                        => $domain,
            'X-Request-Origin'                      => $originDomain,
            'X-Product-Type'                        => $productType,
            'X-Dashboard-Ip'                        => $clientIp,
            'X-IP-Address'                          => $ipAddress,
            'X-User-Agent'                          => $userAgent,
            'X-Csrf-Token'                          => $csrfToken,
            'Cookie'                                => $cookies,
            'X-Org-Id'                              => $orgId,
            'apollographql-client-name'             => $apolloClientName,
            'apollographql-client-version'          => $apolloClientVersion,
            'X-App-Mode'                            => $appMode,
            'rzpctx-dev-serve-user'                 => $devServeHeader,
            'X-Mobile-Debug-Id'                     => $mobileDebugId,
            'X-Razorpay-Account'                    => $rzpAccount,
            // x-partner-* headers contain meta data used during phantom signup
            Headers::X_PARTNER_APPLICATION_ID       => Request::header(Headers::X_PARTNER_APPLICATION_ID),
            Headers::X_PARTNER_OAUTH_REFERRAL       => Request::header(Headers::X_PARTNER_OAUTH_REFERRAL),
        ];

        if (app('request.ctx')->isOauthRequest() === true)
        {
            $defaultHeaders['X-Mobile-Oauth'] = 'true';
        }

        $this->headers = array_merge($defaultHeaders, $this->headers);
    }

    private function getDataForOutgoingRequest($data)
    {

        $incomingContentType = Request::header('content-type');

        if (str_starts_with($incomingContentType, self::CONTENT_TYPE_JSON) === true)
        {
            return ['json' => $data];
        }
        if (str_starts_with($incomingContentType, self::CONTENT_TYPE_FORM) === true)
        {
            return ['form_params' => $data];
        }
        else if(str_starts_with($incomingContentType, self::CONTENT_TYPE_MULTIPART) === true)
        {
            return ['multipart' => $this->getOutGoingMultipartData($data)];
        }

        return [];
    }

    private function getOutGoingMultipartData($data): array
    {
        $outGoingData = [];

        foreach ($data as $key => $value)
        {
            if(is_int($key) === true)
            {
                $key = strval($key);
            }

            if ($value instanceof SplFileInfo)
            {
                $fileName = $value->getClientOriginalName();

                $outGoingData[] =
                    [
                        'name'     => $key,
                        'contents' => Utils::tryFopen($value, 'r'),
                        'filename' => $fileName
                    ];
            }
            else
            {
                $outGoingData[] =
                    [
                        'name'     => $key,
                        'contents' => $value,
                    ];
            }
        }

        return $outGoingData;
    }



    private function addProxyAuthHeadersIfUserLoggedIn()
    {
        $user = Auth::guard('user')->user();

        $userId = app('request.ctx')->getUserId();

        if ($user)
        {

            $proxyAuthHeaders = [
                'X-Dashboard-User-Id'           => $user->id,
                'X-Dashboard-User-Email'        => $user->email,
                'X-Dashboard-User-Session-Id'   => Session::getId(),
            ];

            $this->headers = array_merge($proxyAuthHeaders, $this->headers);

            $this->appendMerchantHeaderIfValid($user);
        }
        elseif (empty($userId) === false)
        {
            $this->headers['X-Dashboard-User-Id'] = $userId;

            $this->headers['X-Dashboard-Merchant-Id'] = app('request.ctx')->getMerchantId();
        }

        $this->trace->info(TraceCode::GRAPH_REQUEST_X_DASHBOARD_UID_MID, [
            'x-dashboard-merchant-id' => $this->headers['X-Dashboard-Merchant-Id'] ?? null,
            'x-dashboard-user-id'    => $this->headers['X-Dashboard-User-Id'] ?? null,
        ]);

    }

    /**
     * @throws \Exception
     */
    private function appendMerchantHeaderIfValid($user)
    {
        $merchantIdInHeader = Request::header('x-dashboard-merchant-id');

        if ($merchantIdInHeader !== null)
        {
            $merchantInSession = $user
                ->merchants
                ->where('id', $merchantIdInHeader)
                ->first();

            if ($merchantInSession)
            {
                if ($merchantInSession->id === $merchantIdInHeader)
                {
                    $this->headers['X-Dashboard-Merchant-Id'] = $merchantInSession->id;
                }
            }
        }
        else
        {
            $input = Request::all();

            $this->trace->info(TraceCode::GRAPH_REQUEST_OPERATION_WITH_USER_ID,
            [
                'user_id'           => Request::header('x-dashboard-user-id'),
                'operation_name'    => $input['operationName'] ?? null,
            ]);
        }
    }

    private function getErrorResponse($message)
    {
        $baseAppUrl = config('app.url');

        return [
            'errors'    => [
                [
                    'message'   => $message,
                    'extensions'    => [
                        'code'          => 'UNAUTHENTICATED',
                        'url'           => $baseAppUrl.'/user/signin',
                        'status'        => 401,
                        'statusText'    => 'Unauthorized',
                        'is_oauth'      => app('request.ctx')->isOauthRequest(),
                    ]
                ]
            ],

            'data'      => null,
        ];
    }
}
