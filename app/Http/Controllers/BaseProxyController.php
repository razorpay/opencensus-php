<?php

namespace RZP\Http\Controllers;

use App;
use Request;
use ApiResponse;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Error\ErrorCode;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use OpenCensus\Trace\Propagator\ArrayHeaders;
use Psr\Http\Message\RequestInterface;
use RZP\Http\Controllers\Processors\PostProcessor;
use RZP\Http\Controllers\Processors\PreProcessor;
use RZP\Http\Request\Requests;
use RZP\Http\RequestHeader;
use RZP\Trace\TraceCode;

abstract class BaseProxyController extends Controller
{
    protected $service;

    protected $serviceConfig;

    protected $routesMap;

    protected $merchantRoutes = [];
    protected $adminRoutes = [];
    protected $cronRoutes = [];
    protected $adminRouteVsPermission;

    protected $routesVsCacheKeys = [];

    protected $preProcessor;

    protected $postProcessor;

    protected $maskErrors;

    protected $defaultTimeout;

    protected $pathTimeoutMap;

    /**
     * @var mixed
     */
    protected $circuitBreaker;

    /**
     * @var mixed
     */
    protected $app;

    protected $serviceName = null;

    public function __construct(string $service, $maskErrors = false)
    {
        parent::__construct();

        $this->service       = $service;

        $this->serviceConfig = config('services.' . $service);

        $this->maskErrors = $maskErrors;

        $this->app = App::getFacadeRoot();

        $this->circuitBreaker = $this->app['circuit_breaker'];

    }

    protected function getBaseUrl(): string
    {
        return $this->serviceConfig['url'];
    }

    protected abstract function getAuthorizationHeader();

    protected function getCronAuthorizationHeader(){
        return null;
    }

    protected function registerRoutesMap(array $map)
    {
        $this->routesMap = $map;
    }

    protected function registerMerchantRoutes(array $routes)
    {
        $this->merchantRoutes = $routes;
    }

    protected function registerAdminRoutes(array $routes, $adminRouteVsPermission)
    {
        $this->adminRoutes = $routes;
        $this->adminRouteVsPermission = $adminRouteVsPermission;
    }

    protected function registerCronRoutes(array $routes)
    {
        $this->cronRoutes = $routes;
    }

    protected function registerProcessors(PreProcessor $preProcessor, PostProcessor $postProcessor, $serviceName)
    {
        $this->preProcessor  = $preProcessor;
        $this->postProcessor = $postProcessor;
        $this->serviceName   = $serviceName;
    }

    /**
     * Setting default timeout for all paths in seconds
     *
     * @param $timeout
     */
    protected function setDefaultTimeout($timeout)
    {
        $this->defaultTimeout = $timeout;
    }

    protected function setPathTimeoutMap($pathTimeoutMap)
    {
        $this->pathTimeoutMap = $pathTimeoutMap;
    }

    protected function setCacheKeysForRoutes($routesVsCacheKeys)
    {
            $this->routesVsCacheKeys = $routesVsCacheKeys;
    }

    protected function getHeadersForDashboardRequest(array $body = [], string $id = '')
    {
        return [
            'x-merchant-id'    => optional($this->ba->getMerchant())->getId() ?? $id,
            'X-Merchant-Email' => optional($this->ba->getMerchant())->getEmail() ?? '',
            'x-user-id'        => optional($this->ba->getUser())->getId() ?? '',
            'X-User-Role'      => $this->ba->getUserRole() ?? '',
            'X-Auth-Type'      => 'proxy',
            'x-otp'            => $body['otp'] ?? '',
            'X-Task-Id'        => $this->app['request']->getTaskId(),
            'Content-Type'     => 'application/json',
            'Accept'           => 'application/json',
            'Authorization'    => $this->getAuthorizationHeader(),
            'X-Client-ID'      => $this->serviceConfig['client_id'] ?? '',
            'X-Request-ID'     => Request::getTaskId()
        ];
    }

    protected function getRoute($path = null): string
    {
        $routes = array_merge($this->merchantRoutes, $this->adminRoutes);

        $routes = array_merge($routes, $this->cronRoutes);

        foreach ($routes as $route)
        {
            if (preg_match($this->routesMap[$route], $path, $matches) === 1)
            {
                return $route;
            }
        }

        return '';
    }

    public function handleDashboardProxyRequests($path = null)
    {
        $request = Request::instance();
        $body    = $request->all();

        $route = $this->getRoute($path);

        if (empty($route) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        if (($request->method() === 'GET') and
            (empty($body) === false))
        {
            $path .= '?' . http_build_query($body);
        }

        $headers = $this->getHeadersForDashboardRequest($body);

        return $this->sendRequestAndParseResponse($route, $request->method(), $path, $body, $headers);
    }

    public function handleAdminProxyRequests($path = null){
        $request = $this->getRequestInstance();
        $body    = $request->all();

        $route = $this->getRoute($path);

        if (empty($route) === true || empty($this->adminRouteVsPermission[$route]) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        $this->ba->getAdmin()->hasPermissionOrFail($this->adminRouteVsPermission[$route]);

        if ($request->method() === 'GET')
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_HTTP_METHOD_NOT_ALLOWED);
        }

        $headers = $this->getHeadersForAdminRequest($body);

        return $this->sendRequestAndParseResponse($route, $request->method(), $path, $body, $headers);
    }

    public function handleCronProxyRequests($path = null){
        $request = $this->getRequestInstance();
        $body    = $request->all();

        $route = $this->getRoute($path);

        if (empty($route) === true || in_array($route, $this->cronRoutes) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        if ($request->method() === 'GET')
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_HTTP_METHOD_NOT_ALLOWED);
        }

        $body = $this->addCronParamsInPayload($body, $route);

        $this->trace->info(TraceCode::PROXY_CRON_REQUEST, [
            'route'    => $route,
            'body'     => $body,
        ]);

        $headers = $this->getHeadersForCronRequest($body);

        $cronStartTime = Carbon::now()->getTimestamp();

        $res = $this->sendRequestAndParseResponse($route, $request->method(), $path, $body, $headers);

        $this->updateLastCronRunTimeIfApplicable($route, $cronStartTime);

        return $res;
    }

    protected function addCronParamsInPayload($body, $route)
    {
        if (key_exists($route, $this->routesVsCacheKeys) === true)
        {
            $cacheKey = $this->routesVsCacheKeys[$route];

            $body = array_merge($body, [
                'last_cron_run_time' => $this->getLastCronTime($cacheKey),
            ]);
        }

        return $body;
    }

    protected function getLastCronTime($cacheKey): ?int
    {

        $cacheValue = $this->app['cache']->get($cacheKey);

        // if value fetched from cache is null check a default value for cron time is provided.
        // if not provided set current time - 15 minutes as default value
        if($cacheValue === null)
        {
            $defaultLastCronValue = $this->getDefaultLastCronValue();
            return is_null($defaultLastCronValue) ? Carbon::now()->subMinutes(15)->getTimestamp() :
                $defaultLastCronValue;
        }

        return $cacheValue;
    }

    protected function getDefaultLastCronValue(): ?int
    {
        return null;
    }

    protected function updateLastCronRunTimeIfApplicable($route, $cronStartTime)
    {
        if (key_exists($route, $this->routesVsCacheKeys) === true)
        {
            $cacheKey = $this->routesVsCacheKeys[$route];

            $this->app['cache']->put($cacheKey, $cronStartTime);

            $this->trace->info(TraceCode::CRON_LAST_RUN_TIMESTAMP_UPDATED, [
                'updated_last_cron_time'    => $cronStartTime,
            ]);
        }
    }

    public function handleInternalCronProxyRequests($path, $body) {

        $route = $this->getRoute($path);

        $headers = $this->getHeadersForCronRequest($body);

        return $this->sendRequestAndParseResponse($route, 'POST', $path, $body, $headers);
    }

    protected function sendRequestAndParseResponse(
        string $route,
        string $method,
        string $path,
        array $body = [],
        array $headers = [],
        array $options = [])
    {
        $this->circuitBreaker->isAvailable($this->serviceName);

        try
        {
            if (empty($this->preProcessor) === false)

            {
                $body = $this->preProcessor->process($route, $body, []);
            }

            $options = array_merge($options, $this->getOptions($route));

            $resp = $this->sendRequest($headers, $path, $method, $body, $options);

            $parsedResponse = $this->parseResponse($resp->status_code, $resp->body);

            if ($resp->status_code === 200 and empty($this->postProcessor) === false)
            {
                $this->circuitBreaker->success();

                return $this->postProcessor->process($route, $body, $parsedResponse);
            }
            else
            {
                $this->circuitBreaker->failure();

                return $parsedResponse;
            }
        }
        catch (\Exception $e)
        {
            $this->circuitBreaker->failure();

            throw $e;
        }
    }

    protected function sendRequest($headers, $path, $method, $body, $options = [])
    {

        $arrHeaders = new ArrayHeaders($headers);
        $headers    = $arrHeaders->toArray();

        $headers = array_merge($headers, [
            RequestHeader::DEV_SERVE_USER =>  Request::header(RequestHeader::DEV_SERVE_USER)
        ]);

        $baseUrl = $this->getBaseUrl();
        $url     = $baseUrl . '/' . $path;
        $body    = empty($body) ? '{}' : json_encode($body);

        $this->trace->info(TraceCode::PROXY_REQUEST, [
            'path'    => $path,
            'method'  => $method,
            'service' => $this->service,
            'options' => $options
        ]);

        $resp = $this->request($url, $headers, $body, $method, $options);

        $this->trace->info(TraceCode::PROXY_RESPONSE, [
            'status_code' => $resp->status_code,
            'path'        => $path,
            'method'      => $method,
            'service'     => $this->service
        ]);

        return $resp;
    }

    protected function getOptions($routeName)
    {
        $timeout = $this->pathTimeoutMap[$routeName] ?? $this->defaultTimeout;

        return [
            'timeout' => $timeout
        ];
    }

    protected function newRequest(string $method, string $url, string $reqBody, array $headers): RequestInterface
    {
        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();

        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();

        $body = $streamFactory->createStream($reqBody);

        $req = $requestFactory->createRequest($method, $url);

        foreach ($headers as $key => $value)
        {
            $req = $req->withHeader($key, $value);
        }

        return $req->withBody($body);
    }

    protected function parseResponse($code, $body)
    {
        $body = json_decode($body, true);

        if ($this->maskErrors)
        {
            // throwing exception to keep the error response format consistent
            if ($code === 404)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
            }
            else
            {
                if ($code !== 200)
                {
                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_REQUEST_BODY);
                }
            }
        }

        return $body;
    }

    protected function getHeadersForAdminRequest($body)
    {
        return [
            'X-Admin-id'       => optional($this->ba->getAdmin())->getId() ?? '',
            'X-Task-Id'        => $this->app['request']->getTaskId(),
            'Content-Type'     => 'application/json',
            'Accept'           => 'application/json',
            'Authorization'    => $this->getAuthorizationHeader(),
            'X-Request-ID'     => Request::getTaskId(),
            'X-Client-ID'      => $this->serviceConfig['client_id'] ?? ''
        ];
    }

    protected function getHeadersForCronRequest($body)
    {
        return [
            'Content-Type'     => 'application/json',
            'Accept'           => 'application/json',
            'Authorization'    => $this->getCronAuthorizationHeader(),
            'X-Request-ID'     => Request::getTaskId(),
            'X-Client-ID'      => $this->serviceConfig['client_id'] ?? ''
        ];
    }

    public function request(string $url, $headers, $body, $method, array $options)
    {
        return Requests::request($url, $headers, $body, $method, $options);
    }

    public function getRequestInstance()
    {
        $request = Request::instance();

        return $request;
    }
}
