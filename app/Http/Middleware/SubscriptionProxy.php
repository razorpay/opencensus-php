<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;
use Requests_Session;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;

class SubscriptionProxy
{
    protected $app;

    protected $ba;

    protected $router;

    const REQUEST_TIMEOUT = 10;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->ba = $this->app['basicauth'];

        $this->route = $this->app['api.route'];

        $this->trace = $this->app['trace'];

        $this->request = $this->initRequestObject();
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
            'timeout' => self::REQUEST_TIMEOUT,
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

        $this->trace->info(TraceCode::SUBSCRIPTION_SERVICE_PROXY_REQUEST, [
            'request'      => $request->path(),
            'method'       => $request->method(),
            'body'         => $request->post(),
            'query_string' => $request->getQueryString(),
        ]);

        $res = $this->forwardSubscriptionServiceRequest($request);

        return $res;
    }

    protected function forwardSubscriptionServiceRequest(Request $request)
    {
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

        $headers = $this->getHeaders();

        $method = $request->method();

        $response = $this->sendRequestAndParseResponse($url, $method, $body, $headers);

        return $response;
    }

    protected function getHeaders(): array
    {
        $headers = [];

        if ($this->ba->getMerchantId() !== null)
        {
            $headers['X-Razorpay-MerchantId'] = $this->ba->getMerchantId();
        }

        $headers['X-Razorpay-Mode'] = $this->ba->getMode();
        $headers['X-Razorpay-Auth'] = $this->ba->getAuthType();

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
        catch (\Requests_Exception $e)
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
        $code = $response->status_code;
        $body = json_decode($response->body, true);

        $this->trace->info(TraceCode::SUBSCRIPTION_SERVICE_PROXY_RESPONSE, [
            'code' => $code,
            'body' => $body,
        ]);

        return ApiResponse::json($body, $code);
    }

    protected function hasRequestTimedOut(\Requests_Exception $e): bool
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

    protected function shouldProxyToSubscriptionService(): bool
    {
        $isFeatureEnabled = optional($this->ba->getMerchant())->isFeatureEnabled(Feature\Constants::SUBSCRIPTION_V2);

        return (($this->app->environment('testing') === false) and
                ($this->ba->getMode() === Mode::TEST) and
                ($this->route->isSubscriptionProxyRoute() === true) and
                ($isFeatureEnabled === true));
    }
}
