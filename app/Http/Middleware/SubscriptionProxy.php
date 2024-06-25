<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;
use \WpOrg\Requests\Session as Requests_Session;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Razorpay\Edge\Passport\Passport;
use Illuminate\Foundation\Application;

use RZP\Exception;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use RZP\Http\RequestContextV2;
use RZP\Models\Payment\Method as PaymentMethods;
use RZP\Models\Merchant\Methods\Core as MethodsCore;

class SubscriptionProxy
{
    protected $app;

    protected $ba;

    protected $router;

    protected $requestTimeout;

    /**
     * @var \RZP\Services\RazorXClient
     */
    protected $razorx;

    /**
     * @var RequestContextV2
     */
    protected $reqCtx;

    const NAME = 'name';

    const SETTINGS_ENABLED = 'setting_enabled';

    const METHOD_ENABLED = 'method_enabled';

    public function __construct(Application $app)
    {
        $this->requestTimeout = $app['config']->get('app.subscription_proxy_timeout');

        $this->app = $app;

        $this->ba = $this->app['basicauth'];

        $this->route = $this->app['api.route'];

        $this->trace = $this->app['trace'];

        $this->request = $this->initRequestObject();

        $this->razorx = $app['razorx'];

        $this->reqCtx = $app['request.ctx.v2'];
    }

    protected function initRequestObject(): Requests_Session
    {
        $config = config('applications.subscriptions');

        $baseUrl = $config['url'];

        $username = $config['username'];

        $password = $config['secret'];

        $defaultHeaders = [
            'Accept'            => 'application/json',
            'X-Razorpay-TaskId' => $this->app['request']->getTaskId(),
        ];

        $defaultOptions = [
            'timeout' => $this->requestTimeout,
            'auth'    => [$username, $password],
        ];

        $request = new Requests_Session($baseUrl, $defaultHeaders, [], $defaultOptions);

        return $request;
    }

    public function handle(Request $request, Closure $next)
    {
        if ($this->shouldProxyToSubscriptionService() === false)
        {
            return $next($request);
        }

        $body = $this->getRequestBody($request);

        $response = $this->preProcessSubscriptionsSettings($body);

        if(empty($response) === false)
        {
            return ApiResponse::json($response, 400);
        }

        $response = $this->preProcessSubscriptionsCreate($body);

        if(empty($response) === false)
        {
            return ApiResponse::json($response, 400);
        }

        $url = $request->path();

        $body = [];

        if ($request->getQueryString() !== null)
        {
            $url .= '?' . $request->getQueryString();
        }

        if ($request->post() !== null)
        {
            $body = $request->post();
        }

        $headers = $this->getHeaders($request);

        $method = $request->method();

        $this->trace->info(TraceCode::SUBSCRIPTION_SERVICE_PROXY_REQUEST, [
            'request'      => $request->path(),
            'method'       => $request->method(),
            'body'         => $request->post(),
            'query_string' => $request->getQueryString(),
            'has_passport' => array_key_exists(Passport::PASSPORT_JWT_V1, $headers),
        ]);

        $response = $this->sendRequestAndParseResponse($url, $method, $body, $headers);

        return $response;
    }

    protected function getHeaders(Request $request): array
    {
        $headers = [];

        if ($this->ba->getMerchantId() !== null)
        {
            $headers['X-Razorpay-MerchantId'] = $this->ba->getMerchantId();
        }

        $headers['X-Razorpay-Mode']          = $this->ba->getMode();
        $headers['X-Razorpay-Auth']          = $this->ba->getAuthType();
        $headers['X-Razorpay-Proxy']         = $this->ba->isProxyAuth();

        // send appId in case request is via partner auth
        $headers['X-Razorpay-ApplicationId'] = $this->ba->getOAuthApplicationId();

        //
        // If passport exists in request header, from edge, then forward the
        // same to subscriptions service for all but hosted pages. And razorx
        // is used to control ramp.
        //
        // Subscription receives both current headers and passport. Subscription
        // has only one middleware which runs for all routes access, and passport
        // is given priority if it exists. Eventually, subscription will always
        // receive passport and should decide what to do with unidentified requests,
        // maybe including basis route kind.
        //
        $jwt = $request->headers->get(Passport::PASSPORT_JWT_V1);
        if (($this->ignoreHostedPageUrl() === false) and
            (empty($jwt) === false) and
            ($this->reqCtx->passportAttrsMismatch === false))
        {
            $headers[Passport::PASSPORT_JWT_V1] = $jwt;
        }

        $headers[RequestHeader::DEV_SERVE_USER] = $request->header(RequestHeader::DEV_SERVE_USER);

        return $headers;
    }

    protected function sendRequestAndParseResponse(
        string $url,
        string $method,
        array $body = [],
        array $headers = [],
        array $options = [])
    {
        try
        {
            $response = $this->request->request(
                $url,
                $headers,
                $body,
                $method,
                $options);
        }
        catch (\WpOrg\Requests\Exception $e)
        {
            $errorCode = ($this->hasRequestTimedOut($e) === true) ?
                ErrorCode::SERVER_ERROR_SUBSCRIPTION_SERVICE_TIMEOUT :
                ErrorCode::SERVER_ERROR_SUBSCRIPTION_SERVICE_FAILURE;

            throw new Exception\ServerErrorException(
                $e->getMessage(),
                $errorCode
            );
        }

        return $this->parseResponse($response);
    }

    protected function parseResponse($response)
    {
        if ($this->isHostedPageUrl() === true)
        {
            return $this->parseAsHtml($response);
        }

        $code = $response->status_code;
        $body = json_decode($response->body, true);

        $this->trace->info(TraceCode::SUBSCRIPTION_SERVICE_PROXY_RESPONSE, [
            'code' => $code,
            'body' => $body,
        ]);

        $this->processGetSubscriptionsSettingsResponse($body);

        return ApiResponse::json($body, $code);
    }

    protected function parseAsHtml($response)
    {

        $code = $response->status_code;

        $body = $response->body;

        $this->trace->info(TraceCode::SUBSCRIPTION_SERVICE_PROXY_RESPONSE, [
            'code' => $code,
            'body' => $body,
        ]);

        return \Response::make($body);
    }

    protected function hasRequestTimedOut(\WpOrg\Requests\Exception $e): bool
    {
        $message = $e->getMessage();

        return Str::contains($message, [
            'operation timed out',
            'network is unreachable',
            'name or service not known',
            'failed to connect',
            'could not resolve host',
            'resolving timed out',
            'name lookup timed out',
            'connection timed out',
            'aborted due to timeout',
        ]);
    }

    protected function getRequestBody(Request $request)
    {
        if ($request->post() === null)
        {
            return [];
        }

        $queryStringArray = $this->getQueryStringAsArray($request->getQueryString());

        return array_diff_assoc($request->post(), $queryStringArray);
    }

    protected function getQueryStringAsArray(string $queryString = null): array
    {
        $queryStringArray = [];

        if ($queryString === null)
        {
            return $queryStringArray;
        }

        $queryStringChunks = explode('&', $queryString);

        foreach ($queryStringChunks as $queryChunk)
        {
            $queryChunk = urldecode($queryChunk);

            if (str_contains($queryChunk, '=') === true)
            {
                $queryStringArray[str_before($queryChunk, '=')] = str_after($queryChunk, '=');
            }
            else
            {
                $queryStringArray[$queryChunk] = '';
            }
        }

        return $queryStringArray;
    }

    protected function shouldProxyToSubscriptionService(): bool
    {
        if (($this->app->environment('testing') === true) or
            ($this->route->isSubscriptionProxyRoute() === false))
        {
            return false;
        }

        //
        // If it's on the proxy list but not on the feature proxy list,
        // that means we can redirect without checking for the feature
        //
        if ($this->route->isSubscriptionFeatureProxyRoute() === false)
        {
            return true;
        }

        //
        // Doing this check separately so we don't end up
        // fetching merchant features for every single request
        //
        $isFeatureEnabled = optional($this->ba->getMerchant())->isFeatureEnabled(Feature\Constants::SUBSCRIPTION_V2);

        return ($isFeatureEnabled === true);
    }

    protected function isHostedPageUrl(): bool
    {
        $currentRoute = $this->route->getCurrentRouteName();

        if (($currentRoute === 'subscription_view_test') or
            ($currentRoute === 'subscription_view_live') or
            ($currentRoute === 'subscription_view_test_post') or
            ($currentRoute === 'subscription_view_live_post'))
        {
            return true;
        }

        return false;
    }

    protected function ignoreHostedPageUrl(): bool
    {
        $currentRoute = $this->route->getCurrentRouteName();

        if (($currentRoute === 'subscription_view_test') or
            ($currentRoute === 'subscription_view_live') or
            ($currentRoute === 'subscription_view_test_post') or
            ($currentRoute === 'subscription_fetch_hosted_test') or
            ($currentRoute === 'subscription_fetch_hosted_live') or
            ($currentRoute === 'subscription_view_live_post'))
        {
            return true;
        }

        return false;
    }

    protected function preProcessSubscriptionsSettings($input): array
    {
        $response = [];
        if (($this->route->getCurrentRouteName() === 'subscription_settings') and ($input[self::SETTINGS_ENABLED] === '1') and (isset($input[self::NAME]) === true))
        {
            $merchant = $this->ba->getMerchant();
            // proceed only for india merchants
            if(($merchant->getCountry() === 'IN') and (in_array($input[self::NAME], PaymentMethods::$subscriptionsDomesticMethods, true) === true))
            {
                $methodData = (new MethodsCore())->getMethodsForSubscriptionSettings($merchant, $input[self::NAME]);
                $this->trace->info(TraceCode::SUBSCRIPTION_TERMINAL_AND_METHOD_DETAILS, [
                    'Method And Terminal Details'    => $methodData,
                    'merchant'                       => $merchant
                ]);
                [$methodEnabled, $terminalAvailable] = $this->checkMethodAndTerminalAvailability($methodData, $input[self::NAME]);
                return $this->buildMethodsResponse($methodEnabled, $terminalAvailable, $input, $merchant);
            }
        }
        return $response;
    }

    protected function processGetSubscriptionsSettingsResponse(&$body)
    {
        if (($this->route->getCurrentRouteName() === 'subscription_settings_get') and ((empty($body) === false) and (isset($body['items']) === true)))
        {
            $merchant = $this->ba->getMerchant();
            $modifiedItems = [];
            foreach ($body['items'] as $item)
            {
                $method = $item[self::NAME];
                $item[self::METHOD_ENABLED] = '1';
                // check terminal & method enablement for India merchants & domestic methods
                if(($merchant->getCountry() === 'IN') and (in_array($method, PaymentMethods::$subscriptionsDomesticMethods, true) === true))
                {
                    $methodData = (new MethodsCore())->getMethodsForSubscriptionSettings($merchant, $method);
                    $this->trace->info(TraceCode::SUBSCRIPTION_TERMINAL_AND_METHOD_DETAILS, [
                        'Method And Terminal Details'    => $methodData,
                        'merchant'                       => $merchant
                    ]);
                    [$methodEnabled, $terminalAvailable] = $this->checkMethodAndTerminalAvailability($methodData, $method);
                    if(($methodEnabled === false) or ($terminalAvailable === false)){
                        $item[self::METHOD_ENABLED] = '0';
                    }
                }
                $modifiedItems [] = $item;
            }
            $body['items'] = $modifiedItems;
        }
    }

    protected function preProcessSubscriptionsCreate($input): array
    {
        $response = [];
        if ($this->route->getCurrentRouteName() === 'subscription_create')
        {
            $merchant = $this->ba->getMerchant();
            // proceed only for india merchants
            if($merchant->getCountry() === 'IN')
            {
                foreach (PaymentMethods::$subscriptionsDomesticMethods as $method)
                {
                    $methodData = (new MethodsCore())->getMethodsForSubscriptionSettings($merchant, $method);
                    $this->trace->info(TraceCode::SUBSCRIPTION_TERMINAL_AND_METHOD_DETAILS, [
                        'Method & Terminal Details'      => $methodData,
                        'merchant'                       => $merchant
                    ]);
                    [$methodEnabled, $terminalAvailable] = $this->checkMethodAndTerminalAvailability($methodData, $method);
                    if(($methodEnabled === true) and ($terminalAvailable === true))
                    {
                        return $response;
                    }
                }
                return $this->buildErrorRsp();
            }
        }
        return $response;
    }

    protected function checkMethodAndTerminalAvailability($methodData, $method): array
    {
        $methodEnabled = ((isset($methodData[$method][MethodsCore::METHOD_ENABLED])) and ($methodData[$method][MethodsCore::METHOD_ENABLED] === 1));
        $terminalAvailable = ((isset($methodData[$method][MethodsCore::TERMINAL_AVAILABLE])) and ($methodData[$method][MethodsCore::TERMINAL_AVAILABLE] === 1));
        return [$methodEnabled, $terminalAvailable];
    }

    protected function buildMethodsResponse($methodEnabled, $terminalAvailable, $input, $merchant): array
    {
        $response = [];

        if(($methodEnabled === false) or ($terminalAvailable === false))
        {
            $this->trace->info(TraceCode::SUBSCRIPTION_TERMINAL_OR_METHOD_NOT_ENABLED, [
                'input'             => $input,
                'merchant'          => $merchant,
                'methodEnabled'     => $methodEnabled,
                'terminalAvailable' => $terminalAvailable
            ]);
            return $this->buildErrorRsp();
        }

        return $response;
    }

    protected function buildErrorRsp(): array
    {
        $response = [];
        $response['error']['code']  = 'BAD_REQUEST_METHOD_NOT_ENABLED';
        $response['error']['description']  = 'There is no recurring payment method enabled. Please visit Subscriptions Settings tab to raise a request.';
        return $response;
    }
}
