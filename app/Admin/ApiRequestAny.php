<?php

namespace App\Admin;

use Auth;
use Input;
use Route;
use Trace;
use Config;
use Session;
use Request;
use App\Lib\Util;
use App\Http\Headers;
use App\User\Identity;
use App\Trace\SpanTrace;
use GuzzleHttp\Psr7\Utils;
use App\Metrics\Constants;
use Lcobucci\JWT\Token\Parser;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Promise\Promise;
use Razorpay\Api\Errors as RZPErrors;
use App\Admin\Service as AdminService;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Razorpay\Api\Errors\BadRequestError;
use App\User\Constants as UserConstants;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ConnectException;
use App\Constants\Constants as AppConstants;
use OpenCensus\Trace\Propagator\ArrayHeaders;

use App\Http\ApiUrl;
use App\Trace\TraceCode;
use App\Merchant\Service as MerchantService;

class ApiRequestAny
{
    /**
     * Guzzle Client instance
     * @var Guzzle
     */
    protected $client;

    protected $mode;

    protected $path;

    protected $routeMap;

    protected $shouldProcessInput       = true;

    const RAZORPAY_ACCOUNT_HEADER       = 'X-Razorpay-Account';

    const API_ROUTE_NAME_HEADER         = 'Api-Route-Name';

    const API_ROUTE_PATH_PATTERN_HEADER = 'Api-Path-Pattern';

    const CONTENT_TYPE_JSON             = 'application/json';

    const CONTENT_TYPE_FORM             = 'application/x-www-form-urlencoded';

    const CONTENT_TYPE_MULTIPART_PREFIX = 'multipart/form-data';

    const ADMIN_AS_MERCHANT             = 'admin_as_merchant';

    // field passed by the API in case of errors are exposed
    // dashboard handles these error in a custom way
    // by passing the data to the frontend
    const INTERNAL_ERROR_CODE = 'internal_error_code';

    const INTERNAL_ERROR_CODES = [
        'BAD_REQUEST_LOCKED_USER_LOGIN',
        'BAD_REQUEST_USER_LOGIN_2FA_SETUP_REQUIRED',
        'BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP',
        'BAD_REQUEST_2FA_LOGIN_INCORRECT_PASSWORD',
        'BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED',
        'BAD_REQUEST_USER_2FA_LOGIN_PASSWORD_REQUIRED',
        'BAD_REQUEST_2FA_SETUP_USER_2FA_NOT_ENABLED',
        'BAD_REQUEST_2FA_SETUP_ACCOUNT_LOCKED',
        'BAD_REQUEST_RESTRICTED_USER_CANNOT_SETUP_2FA',
        'BAD_REQUEST_USER_2FA_ALREADY_SETUP',
        'BAD_REQUEST_2FA_SETUP_INCORRECT_OTP',
        'BAD_REQUEST_USER_2FA_SETUP_REQUIRED',
        'BAD_REQUEST_ADMIN_2FA_LOGIN_OTP_REQUIRED',
        'BAD_REQUEST_LOCKED_ADMIN_LOGIN',
        'BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE',
        'BAD_REQUEST_NO_ACCOUNTS_ASSOCIATED',
        'BAD_REQUEST_MULTIPLE_ACCOUNTS_ASSOCIATED',
        'BAD_REQUEST_LOGIN_OTP_VERIFICATION_THRESHOLD_EXHAUSTED',
        'BAD_REQUEST_EMAIL_LOGIN_OTP_SEND_THRESHOLD_EXHAUSTED',
        'BAD_REQUEST_EMAIL_OTP_VERIFICATION_THRESHOLD_EXHAUSTED',
        'BAD_REQUEST_EMAIL_NOT_VERIFIED',
        'BAD_REQUEST_CONTACT_MOBILE_NOT_VERIFIED',
        'BAD_REQUEST_2FA_LOGIN_PASSWORD_SUSPENDED',
        'BAD_REQUEST_EMAIL_VERIFICATION_OTP_SEND_THRESHOLD_EXHAUSTED',
        'BAD_REQUEST_VERIFICATION_OTP_VERIFICATION_THRESHOLD_EXHAUSTED',
        'BAD_REQUEST_OTP_LOGIN_LOCKED',
        'BAD_REQUEST_EMAIL_ALREADY_VERIFIED',
        'BAD_REQUEST_CONTACT_MOBILE_ALREADY_VERIFIED',
        'BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED',
        'BAD_REQUEST_OTP_MAXIMUM_ATTEMPTS_REACHED',
        'BAD_REQUEST_MOBILE_OTP_LOGIN_NOT_ALLOWED',
        'BAD_REQUEST_EMAIL_ALREADY_EXISTS',
        'BAD_REQUEST_CONTACT_MOBILE_ALREADY_EXISTS',
        'BAD_REQUEST_EMAIL_SIGNUP_OTP_SEND_THRESHOLD_EXHAUSTED',
        'BAD_REQUEST_SIGNUP_OTP_VERIFICATION_THRESHOLD_EXHAUSTED',
        'BAD_REQUEST_PASSWORD_ALREADY_SET'
    ];

    const WHITELISTED_QUERY_PARAMS_ROUTE_PREFIXES = [
        'vendor-payments',
        'gcoms',
        'payments_cross_border',
        'terminals/proxy/qc',
        'magic/analytics/reports',
    ];

    /**
     * Construct a RawApiRequest instance
     *
     * @param string $mode live|test
     * @param string $base_url base url of dashboard
     */
    function __construct(array $options = [])
    {
        // Increase the time limit
        set_time_limit(600);

        // === Mode

        $this->mode = $options['mode'] ?? 'live';

        // === Client Type

        if (empty($options['client_type']) === true)
        {
            $routeName = Route::currentRouteName();

            if (in_array($routeName, ['merchant', 'admin', 'extension_merchant', 'oauth_merchant', 'oauth_user_logout'], true) === false)
            {
                // Default
                $this->clientType = 'user';
            }
            else
            {
                if (in_array($routeName,  ['oauth_merchant', 'oauth_user_logout']))
                {
                    $this->clientType = 'merchant';
                }
                else
                {
                    $this->clientType = $routeName;
                }
            }
        }
        else
        {
            $this->clientType = $options['client_type'];
        }

        // === Headers

        $domain = \Request::server('SERVER_NAME');

        $originDomain = ApiUrl::getRequestOriginUrl();

        $requestedClientIPS = \Request::ips();

        $clientIp = end($requestedClientIPS);

        $requestId = app('request')->requestId;

        $defaultHeaders = [
            'X-Dashboard'           => 'true',
            'X-User-Agent'          => Request::header('User-Agent'),
            'X-Dashboard-Ip'        => $clientIp,
            'X-IP-Address'          => Request::ip(),
            'X-Org-Hostname'        => $domain,
            'X-Request-Origin'      => $originDomain,
            'X-Request-TraceId'     => $requestId,
            Headers::DEV_SERVE_USER => Request::header(Headers::DEV_SERVE_USER),
            Headers::X_RAZORPAY_REQUEST_ID => Request::header(Headers::X_RAZORPAY_REQUEST_ID),
        ];

        if (app('request.ctx')->isOauthRequest() === true)
        {
            $defaultHeaders['X-Mobile-Oauth'] = 'true';
        }

        $orgId = Request::header(Headers::X_ORG_ID);

        if(!empty($orgId))
        {
            $defaultHeaders['X-Org-Id'] = $orgId;
        }

        $headers = $options['headers'] ?? [];

        $headers = array_merge($defaultHeaders, $headers);

        if (empty(Request::header('ledger-tenant')) === false) {
            $headers['ledger-tenant'] = Request::header('ledger-tenant'); // only add the header if it exists
        }

        if (empty(Request::header(Headers::X_SPLITZ_PROJECT)) === false) {
            $headers[Headers::X_SPLITZ_PROJECT] = Request::header(Headers::X_SPLITZ_PROJECT);
        }

        // === Request options

        $this->options = [
            'headers' => $headers,
        ];

        // === Guzzle client
        if (empty($options[AppConstants::HTTP_CLIENT]) === true)
        {
            $this->client = new Guzzle([
               'base_uri' => ApiUrl::getApiBaseUrl(),
               'defaults' => [
                   'timeout' => Config::get('api.request_timeout'),
               ]
           ]);
        }
        else
        {
            $this->client = $options[AppConstants::HTTP_CLIENT];
        }

        // === Get API Route map config

        $this->routeMap = Config::get('api-route-map');

        // === Process client specific headers

        if ($this->clientType === 'extension_merchant')
        {
            $this->processExtensionAuthHeaders();
        }
        else
        {
            $this->processAuthHeaders();
        }

        // === Forward cookies from the api

        $this->forwardCookies();

        // === Auto process input

        $processInput = $options['process_input'] ?? true;

        $useCustomFileKeys = $options['custom_file_keys'] ?? false;

        if ($processInput === true)
        {
            $this->processInput(null, $useCustomFileKeys);
        }
    }

    public function processAuthHeaders() {

        $clientType = $this->clientType;

        $baUser = null;

        if (empty($clientType) === false) {

            if ($clientType === 'merchant')
            {
                $user = Auth::guard('user')->user();

                if (empty($user) === false)
                {
                    $currentMerchant = $user->currentMerchant();

                    if (empty($currentMerchant) === true)
                    {
                        throw new BadRequestError(
                            'Invalid merchant request.',
                            \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                            400);
                    }

                    $twoFaVerified = Session::get(UserConstants::TWO_FA_VERIFIED, false);

                    $this->options['headers']['X-Dashboard-User-Role'] = $currentMerchant->role;

                    $this->options['headers']['X-Dashboard-User-Id'] = $user->id;

                    $this->options['headers']['X-Dashboard-User-Email'] = $user->email;

                    // Only string can be sent in http headers
                    // bool value is converted to '1' for true & '0' for false
                    $this->options['headers']['X-Dashboard-User-2FA-Verified'] =
                        $twoFaVerified ? 'true' : 'false';

                    $isAdminAsMerchant = (new AdminService())->isAdminLoggedIn();

                    $this->options[Headers::HEADERS][Headers::X_DASHBOARD_ADMIN_AS_MERCHANT] = $isAdminAsMerchant;

                    Trace::info(TraceCode::ADMIN_LOGGED_IN_AS_MERCHANT, [
                        self::ADMIN_AS_MERCHANT => $isAdminAsMerchant
                    ]);

                }
                else
                {
                    if (app('request.ctx')->isOauthRequest() === true)
                    {
                        $userId = app('request.ctx')->getUserId();

                        $this->options['headers']['X-Dashboard-User-Id'] = $userId;
                    }
                }

                $accountId = Request::header(self::RAZORPAY_ACCOUNT_HEADER);

                if ($accountId)
                {
                    $this->options['headers'][self::RAZORPAY_ACCOUNT_HEADER] = $accountId;
                }

                if (app('request.ctx')->isOauthRequest() === true)
                {
                    $mid = app('request.ctx')->getMerchantId();
                }

                $currenMerchantId = $currentMerchant->id ?? $mid;

                $baUser = $this->mode . '_' . $currenMerchantId;

                $pass = Config::get('api.auth_pass');
            }
            else if ($clientType === 'admin')
            {
                $adminUser = Auth::guard('api')->user();

                if (empty($adminUser) === true)
                {
                    throw new BadRequestError(
                        'Invalid admin request.',
                        \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                        400);
                }

                $adminUsername = $adminUser->username ?? null;

                $this->options['headers']['X-Dashboard-Admin-Username'] = $adminUsername;

                $adminEmail = $adminUser->email ?? null;

                $this->options['headers']['X-Dashboard-Admin-Email'] = $adminEmail;

                $this->options['headers']['X-Admin-Token'] = $adminUser->token;

                $accountId = Request::header(self::RAZORPAY_ACCOUNT_HEADER);

                if ($accountId)
                {
                    $this->options['headers'][self::RAZORPAY_ACCOUNT_HEADER] = $accountId;
                }

                $baUser = $this->mode;

                $pass = Config::get('api.admin_auth_pass');

                // TODO: Remove this once debug is completed.
                $x = null;
                if ($pass !== null)
                {
                    $x = (strlen($pass) >= 4) ? substr($pass, 0, 2) . substr($pass, -2) : null;
                }
                Trace::info(TraceCode::ADMIN_ROUTE_DEBUG, [
                    'admin_user_mode'   => $baUser,
                    'admin_user_value'  => $x,
                ]);
            }
            else if ($clientType === 'user')
            {
                // NOTE: Merchant will be able to access the user/guest routes

                $user = Auth::guard('user')->user();

                if (app('request.ctx')->isOauthRequest() === true)
                {
                    $userId = app('request.ctx')->getUserId();

                    $this->options['headers']['X-Dashboard-User-Id'] = $userId;
                }

                if (empty($user) === false)
                {
                    $this->options['headers']['X-Dashboard-User-Id'] = $user->id;

                    $this->options['headers']['X-Dashboard-User-Email'] = $user->email;
                }

                Trace::info(TraceCode::USER_CONTEXT_LOG, [
                    'user_id'            => $user ? $user->id : null,
                    'user_email'         => $user ? $user->email : null,
                    'headers'            => $this->options['headers'],
                ]);

                // NOTE: We should NEVER hit this as Dashboard internal.
                $baUser = 'live';

                $pass = Config::get('api.auth_guest_pass');
            }
            else if ($clientType === 'internal')
            {
                // used only by the dasboard backend.
                $baUser = 'live';

                $pass = Config::get('api.auth_internal_pass');
            }
        }

        // Set BasicAuth creds
        if (empty($baUser) === false)
        {
            $this->options['auth'] = [
                'rzp_' . $baUser,
                $pass
            ];
        }

        return $this;
    }

    public function processExtensionAuthHeaders(): void
    {
        $jwtToken = Request::header(Headers::JWT_TOKEN);

        if(is_string($jwtToken) === false)
        {
            $jwtToken = $jwtToken->toString();
        }

        $token = (new Parser(new JoseEncoder()))->parse($jwtToken);

        $merchantId = $token->claims()->get('merchant_id');

        $userId = $token->claims()->get('user_id');

        app('trace')->info(TraceCode::VERIFY_JWT_EXTENSION_AUTH_HEADER, [
            'merchant_id' => $merchantId,
            'user_id'     => $userId
        ]);

        $baUser = null;

        $pass = null;

        if (empty($userId) === false)
        {
            $this->options['headers']['X-Dashboard-User-Id'] = $userId;
        }

        if (empty($merchantId) === false)
        {
            $baUser = $this->mode . '_' . $merchantId;

            $pass = Config::get('api.auth_pass');
        }

        if (empty($baUser) === false)
        {
            // Set BasicAuth creds
            if (empty($baUser) === false)
            {
                $this->options['auth'] = [
                    'rzp_' . $baUser,
                    $pass
                ];
            }
        }
    }

    // process body according to content-type
    public function processInput($data = null, $useCustomFileKeys = false)
    {
        $input = $data ?? Request::all();

        $defaultContentType = self::CONTENT_TYPE_JSON;

        $contentType = Request::header('content_type', $defaultContentType);

        // Laravel is not considering empty string('') as empty header in Request::header
        if (empty($contentType) === true)
        {
            $contentType = $defaultContentType;
        }

        // auth check just for precaution, so that guests do not upload files
        if (str_starts_with($contentType, self::CONTENT_TYPE_MULTIPART_PREFIX) === true)
        {
            $data = [];

            foreach ($input as $key => $val)
            {
                if (is_array($val))
                {
                    $input = $this->flatten($input, $val, $key);

                    unset($input[$key]);
                }
            }

            foreach ($input as $key => $val)
            {
                if(is_int($key) === true)
                {
                    $key = strval($key);
                }

                if ($val instanceof \SplFileInfo)
                {
                    $fileName = $val->getClientOriginalName();

                    // used in case of file upload through epos
                    if ($useCustomFileKeys === true)
                    {
                        $oldKey = $key;

                        $key = MerchantService::UPLOAD_KEYS[$key];

                        unset($input[$oldKey]);
                    }

                    $data[] =
                    [
                        'name'     => $key,
                        'contents' => Utils::tryFopen($val, 'r'),
                        'filename' => $fileName
                    ];

                }
                else
                {
                    $data[] =
                        [
                            'name'     => $key,
                            'contents' => $val,
                        ];
                }
            }

            $this->options['multipart'] = $data;
        }

        else if (str_starts_with($contentType, self::CONTENT_TYPE_FORM) === true)
        {
            $this->options['form_params'] = $input;
        }
        else if (str_starts_with($contentType, self::CONTENT_TYPE_JSON) === true)
        {
            $this->options['json'] = $input;
        }
        else
        {
            $this->options['body'] = $input;
        }

        return $this;
    }

    /**
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    public function sendAsyncPromise($path, $method = null): \GuzzleHttp\Promise\PromiseInterface
    {
        $method = $method ?? Request::method();

        $currentRouteName = \Route::currentRouteName() ?? 'unknown_route';

        $apiRouteCircuitBreaker = new ApiRouteCircuitBreaker($path, $method, $currentRouteName);

        $apiRouteCircuitBreaker->validateRouteCircuitIsOpen($path, $method);

        $spanOptions = (new ApiRequestSpan($this->client))::getRequestSpanOptions(ApiUrl::getApiBaseUrl().$path);

        $path = str_replace('://', '', $path);

        return (new ApiRequestSpan($this->client))->wrapAsyncRequest(
            $method,
            $path,
            [
                'options' => $this->options,
                'headers' => $this->options['headers'] ?? [],
            ],
            $spanOptions,
        );
    }

    static function millitime(): int
    {
        return round(microtime(true) * 1000);
    }

    /**
     * Fires the request to the API
     * @return array standard response
     */
    public function send($path, $method = null)
    {
        $app = \App::getFacadeRoot();

        $exception = null;
        $errors = [];
        $response = null;
        $httpCode = null;
        $method = $method ?? Request::method();
        $currentRouteName = \Route::currentRouteName() ?? 'unknown_route';

        $apiRouteCircuitBreaker = new ApiRouteCircuitBreaker($path, $method, $currentRouteName);

        $apiRouteCircuitBreaker->validateRouteCircuitIsOpen($path, $method);

        $apiPathName = $apiRouteCircuitBreaker->getApiPathName();

        $spanOptions = (new ApiRequestSpan($this->client))::getRequestSpanOptions(ApiUrl::getApiBaseUrl().$path);

        // In some cases (for instance dashboard merchant searches)
        // $path ends up having URLs which triggers `cURL error 6: Could not resolve host`
        // because Guzzle doesn't attach $path to the base_url set above
        // if it contains `://`
        $path = str_replace('://', '', $path);


        $input = Request::all();

        $path = $this->updatePathWithQueryParams($path, $method, $input);

        $start_time = self::millitime();

        try
        {
            $client = (new ApiRequestSpan($this->client))->wrapRequestInSpan(
                $method,
                $path,
                [
                    'options' => $this->options,
                    'headers' => $this->options['headers']??[],
                    ],
                $spanOptions
            );

            $end_time = self::millitime();

            $time_taken = $end_time - $start_time;

            // log if response time is more then 180 seconds
            if ($time_taken > 180)
            {
                Trace::info(TraceCode::API_SLOW_RESPONSE_CALL, [
                    'api_response_time' => $time_taken,
                ]);
            }

            if ($this->debugLogsEnable() === true)
            {
                Trace::info(TraceCode::API_RESPONSE_METRIC, [
                    'api_response_time' => $time_taken,
                    'path'              => $path,
                    '$method'           => $method,
                    'isBankingRequest'  => ApiUrl::isBankingOriginRequest(),
                    'isPGRequest'       => ApiUrl::isPrimaryOriginRequest(),
                ]);
            }

            try
            {
                $apiRouteName     = $client->getHeader(self::API_ROUTE_NAME_HEADER);

                $apiPathPattern   = $client->getHeader(self::API_ROUTE_PATH_PATTERN_HEADER);

                $apiRouteCircuitBreaker->saveApiRouteDetails($apiRouteName[0], $apiPathPattern[0]);

                $apiRouteCircuitBreaker->success();
            }
            catch(\Exception $e)
            {
                Trace::info(TraceCode::API_CIRCUIT_BREAKER_EXCEPTION, [
                    'message'     => $e->getMessage(),
                    'line_number' => $e->getLine()
                ]);
            }

            $httpCode = $client->getStatusCode();

            try
            {
                $clientBody = $client->getBody();
                $responseBodySize = strlen($clientBody);

                $apiPathName = $apiRouteCircuitBreaker->getApiPathName();

                $dimensions = $this->getApiMetricDimensions($httpCode, $currentRouteName, $apiPathName, $method, $time_taken, $responseBodySize);

                $app['metrics']->count(Constants::METRIC_COUNTER_HTTP_REQUESTS_API_DOWNSTREAM, Constants::EVENT_COUNT_ONE, $dimensions);

                $app['metrics']->histogram(Constants::METRIC_COUNTER_HTTP_REQUESTS_API_DOWNSTREAM_DURATION, $time_taken, $dimensions);
            }
            catch (\Throwable $t)
            {
                $app['trace']->warning(TraceCode::PUSH_METRICS_FAILED, [
                    'message' => $t->getMessage() ?? 'unknown_message',
                    'location' => 'send handler'
                ]);
            }

            $response = json_decode($clientBody, true);
            try {
                app('edgeResponseForwarder')->setHeaders($path, $method, $client->getheaders());
                app('edgeMismatchRecorder')->setEdgeData($path, $method, $client->getheaders());
            } catch (\Throwable $e) {
                app('trace')->warning(TraceCode::EDGE_USER_AUTH_MISC_CODE, [
                    'trace' => $e->getTrace() ?? "unknown_trace",
                    'message' => $e->getMessage() ?? "unknown_message"
                ]);
            }

            return [null, $response, $httpCode];
        }
        catch(\GuzzleHttp\Exception\ClientException $e)
        {
            $end_time = self::millitime();

            $time_taken = $end_time - $start_time;

            $json = json_decode($e->getResponse()->getBody(), true);
            $httpCode = $e->getResponse()->getStatusCode();
            $errors = [ $this->getApiErrorDescription($json)];

            //in case of 2fa api calls we need the _internal passed by the api
            // and dashboard will consume that _internal. For eg.
            // even if username and password is correct we can have failures, if otp was not passed.
            // dashboard needs to explicitly handle these issues.
            if ((empty($json['error']['_internal']) === false) and
                (empty($json['error']['_internal'][self::INTERNAL_ERROR_CODE]) === false) and
                (in_array($json['error']['_internal'][self::INTERNAL_ERROR_CODE], self::INTERNAL_ERROR_CODES) === true))
            {
                $errors = [
                    self::INTERNAL_ERROR_CODE => $json['error']['_internal'][self::INTERNAL_ERROR_CODE],
                    'description'             => $this->getApiErrorDescription($json),
                    'status_code'             => $httpCode,
                    'code'                    => $json['error']['code'],
                    '_internal'               => $json['error']['_internal'] ?? [],
                ];
            }

            Trace::error(
                TraceCode::API_CLIENT_EXCEPTION,
                [
                    'message'           => $e->getMessage(),
                    'api_status_code'   => $httpCode,
                    'path'              => $path,
                    '$method'           => $method,
                ]);
            try
            {
                $dimensions = $this->getApiMetricDimensions($httpCode, $currentRouteName, $apiPathName, $method, $time_taken);

                $app['metrics']->count(Constants::METRIC_COUNTER_HTTP_REQUESTS_API_DOWNSTREAM, Constants::EVENT_COUNT_ONE, $dimensions);

                $app['metrics']->histogram(Constants::METRIC_COUNTER_HTTP_REQUESTS_API_DOWNSTREAM_DURATION, $time_taken, $dimensions);
            }
            catch (\Throwable $t)
            {
                $app['trace']->warning(TraceCode::PUSH_METRICS_FAILED, [
                    'message' => $t->getMessage() ?? 'unknown_message',
                ]);
            }
        }
        catch(\GuzzleHttp\Exception\ServerException $e)
        {
            $end_time = self::millitime();

            $time_taken = $end_time - $start_time;

            $exception = $e;

            $httpCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $errorMessage = $e->getMessage();

            // if $httpCode is 5XX then set the default error message
            if ($httpCode >= 500 && $httpCode < 600)
            {
                $errorMessage = "Dear merchant, We're currently fixing an unexpected issue. Sorry for any inconvenience!";
            }

            $errors = [$errorMessage];

            Trace::error(
                TraceCode::API_SERVER_EXCEPTION,
                [
                    'message'           => $e->getMessage(),
                    'api_status_code'   => $httpCode,
                    'path'              => $path,
                    '$method'           => $method,
                ]);

            try
            {
                $dimensions = $this->getApiMetricDimensions($httpCode, $currentRouteName, $apiPathName, $method, $time_taken);

                $app['metrics']->count(Constants::METRIC_COUNTER_HTTP_REQUESTS_API_DOWNSTREAM, Constants::EVENT_COUNT_ONE, $dimensions);

                $app['metrics']->histogram(Constants::METRIC_COUNTER_HTTP_REQUESTS_API_DOWNSTREAM_DURATION, $time_taken, $dimensions);
            }
            catch (\Throwable $t)
            {
                $app['trace']->warning(TraceCode::PUSH_METRICS_FAILED, [
                    'message' => $t->getMessage() ?? 'unknown_message',
                ]);
            }
        }
        // This captures all the errors that might happen for now
        catch(ConnectException $e)
        {
            $end_time = self::millitime();

            $time_taken = $end_time - $start_time;

            $exception = $e;
            $errors = ["Error in connecting to API"];

            $httpCode = $e->getCode() ?? null;

            app('trace')->error(
                TraceCode::API_CONNECTION_EXCEPTION,
                [
                    'message'           => $e->getMessage(),
                    'path'              => $path,
                    '$method'           => $method,
                ]);

            try
            {
                $dimensions = $this->getApiMetricDimensions($httpCode, $currentRouteName, $apiPathName, $method, $time_taken);

                $app['metrics']->count(Constants::METRIC_COUNTER_HTTP_REQUESTS_API_DOWNSTREAM, Constants::EVENT_COUNT_ONE, $dimensions);

                $app['metrics']->histogram(Constants::METRIC_COUNTER_HTTP_REQUESTS_API_DOWNSTREAM_DURATION, $time_taken, $dimensions);
            }
            catch (\Throwable $t)
            {
                $app['trace']->warning(TraceCode::PUSH_METRICS_FAILED, [
                    'message' => $t->getMessage() ?? 'unknown_message',
                ]);
            }
        }
        catch(GuzzleException $e)
        {
            $end_time = self::millitime();

            $time_taken = $end_time - $start_time;

            $exception = $e;
            $errors = [$e->getMessage()];

            $httpCode = $e->getCode() ?? null;

            Trace::error(
                TraceCode::API_GUZZLE_EXCEPTION,
                [
                    'message'           => $e->getMessage(),
                    'api_status_code'   => $httpCode,
                    'path'              => $path,
                    '$method'           => $method,
                ]);

            try
            {
                $dimensions = $this->getApiMetricDimensions($httpCode, $currentRouteName, $apiPathName, $method, $time_taken);

                $app['metrics']->count(Constants::METRIC_COUNTER_HTTP_REQUESTS_API_DOWNSTREAM, Constants::EVENT_COUNT_ONE, $dimensions);

                $app['metrics']->histogram(Constants::METRIC_COUNTER_HTTP_REQUESTS_API_DOWNSTREAM_DURATION, $time_taken, $dimensions);
            }
            catch (\Throwable $t)
            {
                $app['trace']->warning(TraceCode::PUSH_METRICS_FAILED, [
                    'message' => $t->getMessage() ?? 'unknown_message',
                ]);
            }
        }
        catch(RZPErrors\Error $e)
        {
            $end_time = self::millitime();

            $time_taken = $end_time - $start_time;

            $exception = $e;

            $errors = [$e->getMessage()];

            $httpCode = $e->getCode() ?? null;

            Trace::error(
                TraceCode::API_RZP_EXCEPTION,
                [
                    'message'           => $e->getMessage(),
                    'api_status_code'   => $httpCode,
                    'path'              => $path,
                    '$method'           => $method,
                ]);

            try
            {
                $dimensions = $this->getApiMetricDimensions($httpCode, $currentRouteName, $apiPathName, $method, $time_taken);

                $app['metrics']->count(Constants::METRIC_COUNTER_HTTP_REQUESTS_API_DOWNSTREAM, Constants::EVENT_COUNT_ONE, $dimensions);
                $app['metrics']->histogram(Constants::METRIC_COUNTER_HTTP_REQUESTS_API_DOWNSTREAM_DURATION, $time_taken, $dimensions);
            }
            catch (\Throwable $t)
            {
                $app['trace']->warning(TraceCode::PUSH_METRICS_FAILED, [
                    'message' => $t->getMessage() ?? 'unknown_message',
                ]);
            }
        }

        // Logs non-client side exceptions.
        // Use case: Request didn't reach API, or failed with 5xx before API made
        // a log of it. In such case we don't know what happened. Dashboard as a
        // client should at least log for all server errors received from API.
        if ($exception !== null)
        {
            $data = [
                'message'           => $e->getMessage(),
                'api_status_code'   => $httpCode,
            ];

            Trace::error(
                TraceCode::API_REQUEST_FAILURE,
                $data);
            try
            {
                $apiRouteCircuitBreaker->failure($data);
            }
            catch(\Exception $e)
            {
                Trace::info(TraceCode::API_CIRCUIT_BREAKER_EXCEPTION, [
                    'message'     => $e->getMessage(),
                    'line_number' => $e->getLine()
                ]);
            }
        }
        return [$errors, null, $httpCode];
    }

    protected function flatten($parent, $array, $prefix)
    {

        foreach ($array as $key => $value) {
            if (is_array($value))
            {
                $parent = $this->flatten($parent, $value, $prefix.'['.$key.']');
            }
            else
            {
                $parent[$prefix.'['.$key.']'] = $value;
            }
        }

        return $parent;
    }

    /**
     * @param $httpCode
     * @param $currentRouteName
     * @param $apiPathName
     * @param $method
     * @param $time_taken
     *
     * @return array
     */
    public function getApiMetricDimensions($httpCode, $currentRouteName, $apiPathName, $method, $time_taken, $responseBodySize = 0): array
    {
        $domain = \Request::server('SERVER_NAME');

        $dimensions = [
            Constants::LABEL_HTTP_REQUESTS_ORIGIN                           => ApiUrl::getRequestOrigin(),
            Constants::LABEL_HTTP_REQUESTS_DOMAIN                           => $domain ?? 'unknown_domain',
            Constants::LABEL_HTTP_REQUESTS_API_DOWNSTREAM_STATUS            => $httpCode ?? 'unknown_status',
            Constants::LABEL_HTTP_REQUESTS_API_DOWNSTREAM_DASHBOARD_ROUTE   => $currentRouteName ?? 'unknown_route',
            Constants::LABEL_HTTP_REQUESTS_API_DOWNSTREAM_PRODUCT           => ApiUrl::isPrimaryOriginRequest() ? Constants::PRIMARY : Constants::BANKING ,
            Constants::LABEL_HTTP_REQUESTS_API_DOWNSTREAM_DASHBOARD_METHOD  => $method,
            Constants::LABEL_HTTP_REQUESTS_API_DOWNSTREAM_API_ROUTE_NAME    => $apiPathName,
        ];

        $app = \App::getFacadeRoot();

        try {
            // Attach more details to capture admin and merchant details
            if (Auth::guard('api')->check() === true) {
                $adminUser = Auth::guard('api')->user();
                $adminEmail = $adminUser->email ?? null;

                // Add more details in dimentions
                if ($this->clientType === 'admin') {
                    $adminEmail = $adminUser->email ?? null;
                    $adminId = $adminUser->id ?? null;

                    $dimensions = array_merge($dimensions, [
                        Constants::LABEL_HTTP_REQUESTS_API_ADMIN_EMAIL => $adminEmail,
                        Constants::LABEL_HTTP_REQUESTS_API_ADMIN_ID => $adminId,
                        Constants::LABEL_HTTP_REQUESTS_API_RESPONSE_BODY_SIZE => $responseBodySize,
                    ]);
                }
                else if ($this->clientType === 'merchant') {

                    $user = Auth::guard('user')->user();
                    $currentMerchant = $user->currentMerchant();
                    $merchantId = $currentMerchant->id ?? null;

                    $dimensions = array_merge($dimensions, [
                        Constants::LABEL_HTTP_REQUESTS_API_ADMIN_EMAIL => $adminEmail,
                        Constants::LABEL_HTTP_REQUESTS_API_RESPONSE_BODY_SIZE => $responseBodySize,
                        Constants::LABEL_HTTP_REQUESTS_API_MERCHANT_ID => $merchantId,
                    ]);
                }
            }
        }
        catch (\Throwable $t)
        {
            $app['trace']->warning(TraceCode::PUSH_METRICS_FAILED, [
                'message' => $t->getMessage() ?? 'unknown_message',
                'location' => 'getApiMetricDimensions handler'
            ]);
        }

        $app['trace']->info(TraceCode::API_RESPONSE_METRIC, $dimensions + [Constants::LABEL_HTTP_REQUESTS_API_DOWNSTREAM_API_RESPONSE_TIME => $time_taken]);

        return $dimensions;
    }

    public function forwardCookies()
    {
        // UTM cookies needs forwarding with some manipulation because PHP cookies only accept ASCII
        if (empty($_COOKIE['rzp_utm']) === false)
        {
            $cookie = $_COOKIE['rzp_utm'];

            $cookie = str_replace('+', '%2B', $cookie);

            if(isset($this->options['cookies']['rzp_utm']) === false)
            {
                $this->options['cookies']['rzp_utm'] = $cookie;
            }
        }

        // Forward razorx cookies cause this will be available only in testing mode.
        if (empty($_COOKIE['razorx']) === false)
        {
            $cookie = $_COOKIE['razorx'];

            if(isset($this->options['cookies']['razorx']) === false)
            {
                $this->options['cookies']['razorx'] = $cookie;
            }
        }

        //Forwarding _ga and gclid cookies for Google Analytics tracking
        if (empty($_COOKIE['_ga']) === false and isset($this->options['cookies']['_ga']) === false)
        {
            $this->options['cookies']['_ga'] = $_COOKIE['_ga'];
        }

        if (empty($_COOKIE['gclid']) === false and isset($this->options['cookies']['gclid']) === false)
        {
            $this->options['cookies']['gclid'] = $_COOKIE['gclid'];
        }

        // Forwarding magic_analytics_oauth_csrf cookies for Handling Magic OAuth
        if (empty($_COOKIE['magic_analytics_oauth_csrf']) === false)
        {
            $cookie = $_COOKIE['magic_analytics_oauth_csrf'];

            $cookie = str_replace('+', '%2B', $cookie);

            if(isset($this->options['cookies']['magic_analytics_oauth_csrf']) === false)
            {
                $this->options['cookies']['magic_analytics_oauth_csrf'] = $cookie;
            }
        }
    }

    public function debugLogsEnable()
    {
        $baseUrl = ApiUrl::getApiBaseUrl();

        $env = \App::environment();

        $allowedHosts = [
            'dev'        => '*',
            'dev_docker' => '*',
            'beta'       => [
                'https://beta-api.razorpay.in/v1/',
                'https://beta-api.stage.razorpay.in/v1/',
            ],
            'production' => [
                'https://api-dark.razorpay.com/v1/',
            ],
        ];

        $allowedHostsEnv = $allowedHosts[$env] ?? [];

        return (($allowedHostsEnv === '*') or
                (in_array($baseUrl, $allowedHostsEnv, true) === true));
    }

    public function addCookiesForPath(array $cookiesToAdd)
    {
        foreach ($cookiesToAdd as $cookieName)
        {
            if ($cookieName === 'clientId' and
                empty($_COOKIE['clientId']) === false) {

                $cookie = $_COOKIE['clientId'];

                $cookie = str_replace('+', '%2B', $cookie);

                if(isset($this->options['cookies']['clientId']) === false)
                {
                    // name is changed here because API accepts client Id like this
                    $this->options['cookies']['clientId'] = $cookie;
                }
            }
        }
    }

    public function getApiErrorDescription($exceptionData)
    {
        $errorDescription = 'Something went wrong';

        if (empty($exceptionData['error']['description']) === false)
        {
            $errorDescription = $exceptionData['error']['description'];
        }

        return $errorDescription;
    }

    protected function updatePathWithQueryParams(string $path, string $method, array $input = []): string
    {
        if (($method === 'GET') and
            (empty($input) === false) and
            ($this->shouldTransformToQueryParams($path) === true))
        {
            $query = http_build_query($input);

            $path = "{$path}?{$query}";
        }

        return $path;
    }

    protected function shouldTransformToQueryParams(string $path): bool
    {
        return Util::arraySome(
            self::WHITELISTED_QUERY_PARAMS_ROUTE_PREFIXES,
            fn($prefix) => str_starts_with($path, $prefix)
        );
    }
}


