<?php

namespace RZP\Http\Controllers;

use Response;
use Throwable;
use Razorpay\Trace\Logger;
use RZP\Http\RequestHeader;
use Illuminate\Http\Request;
use Razorpay\Edge\Passport\Passport;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Http\Discovery\Psr17FactoryDiscovery;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\IntegrationException;

/**
 * EdgeProxyController is generic controller which can proxy given request to configured upstream host. The routes can
 * be defined as usual in Route.php- which enables authentication, authorization, rate limiting etc. After that the
 * routes should be put in config/edge_proxy.php with its host configuration- this gets used for proxy-ing request.
 *
 * The proxy-ed request will contain passport header signed by api issuer. The upstream should integration with
 * edge's passport sdk.
 *
 * Refs:
 * - https://write.razorpay.com/doc/about-edge-passport-mCa579K52t
 * - https://github.com/razorpay/goutils/tree/master/passport
 * - https://docs.google.com/document/d/1seAdP5gRyok-F7e_Oo0nAijpsczpjXkXiHlMsJ_iGiU#heading=h.f1ejmdsdmoc9.
 *
 */
class EdgeProxyController extends Controller
{
    const METRIC_REQUEST_ERROR_TOTAL = 'edge_proxy_request_error_total';
    const METRIC_REQUEST_LATENCY_MS  = 'edge_proxy_request_latency_ms.histogram';
    const CONTENT_TYPE_JSON          = 'application/json';

    /**
     * @var \Psr\Http\Client\ClientInterface
     */
    protected $httpClient;

    /**
     * Map- <Api's route name, <Host identifier>>
     * @var array
     */
    protected $routeConfig;

    /**
     * Map- <Host identifier, <Host, Auth>>
     * @var array
     */
    protected $hostConfig;

    public function __construct()
    {
        parent::__construct();

        $this->httpClient  = $this->app->edge_proxy_http_client;
        $this->routeConfig = $this->app->config->get('edge_proxy.route_config');
        $this->hostConfig  = $this->app->config->get('edge_proxy.host_config');
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     * @throws Throwable
     */
    public function proxy(Request $request)
    {
        // 1. Gets config for request.
        $routeName = $request->route()->getName();
        $routeCfg = $this->routeConfig[$routeName] ?? [];
        if (empty($routeCfg))
        {
            throw new IntegrationException(null, ErrorCode::SERVER_ERROR_EDGE_PROXY_NO_CONFIG);
        }
        $hostCfg = $this->hostConfig[$routeCfg['host_id']] ?? [];
        if (empty($hostCfg))
        {
            throw new IntegrationException(null, ErrorCode::SERVER_ERROR_EDGE_PROXY_NO_CONFIG);
        }

        $prefixTrim = $hostCfg['path_prefix_to_skip'] ?? "";
        $prefixAdd = $hostCfg['path_prefix_to_add'] ?? "";

        // 2. Prepares proxy request args.
        $host        = $hostCfg['host'];
        $method      = $request->method();
        $path        = $this->getPath($request->path(), $prefixTrim, $prefixAdd);
        $query       = $request->getQueryString();
        $contentType = $request->header(RequestHeader::CONTENT_TYPE);
        $body        = $this->getContent($contentType, $request);
        $auth        = $hostCfg['auth'];
        $devServeHeader = $request->header(RequestHeader::DEV_SERVE_USER);

        // remove trailing slash in host
        $host = rtrim($host,'/');

        if (isset($devServeHeader) === false)
        {
            $devServeHeader = '';
        }
        if (isset($contentType) === false)
        {
            $contentType = self::CONTENT_TYPE_JSON;
        }

        $headers     = [
            RequestHeader::DEV_SERVE_USER => $devServeHeader
        ];

        if (($request->method() === 'GET') and
            (empty($request->all()) === false) and
            (empty($query) === true))
        {
            $query = http_build_query($request->all());
        }

        // 3. Makes request and returns response.
        $response = $this->request($host, $method, $path, $query, $body, $contentType, $auth, $headers);

        $response = Response::make((string) $response->getBody(), $response->getStatusCode());

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }


    /**
     * @param  string $host
     * @param  string $method
     * @param  string $path
     * @param  string $query
     * @param  string $body
     * @param  string $contentType
     * @param  array  $auth
     * @param  array  $headers
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function request(
        string $host,
        string $method,
        string $path,
        string $query = null,
        string $body,
        string $contentType = null,
        array $auth,
        array $headers): ResponseInterface
    {
        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        $bodyStream = $streamFactory->createStream($body);

        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $request = $requestFactory->createRequest($method, $host.'/'.$path.(empty($query) ? '' : '?'.$query))
            ->withBody($bodyStream)
            ->withHeader('Content-Type', $contentType)
            ->withHeader('Authorization', 'Basic '.base64_encode(implode(':', $auth)))
            ->withHeader(Passport::PASSPORT_JWT_V1, $this->app->basicauth->getPassportJwt($host))
            ->withHeader('X-Task-Id', $this->app->request->getTaskId());

        foreach ($headers as $key => $value)
        {
            $request = $request->withHeader($key, $value);
        }

        try
        {
            $requeststartAt = millitime();
            $proxyResponse = $this->httpClient->sendRequest($request);
        }
        catch (Throwable $e)
        {
            $this->trace->count(self::METRIC_REQUEST_ERROR_TOTAL);
            $this->trace->traceException($e, Logger::ERROR, TraceCode::EDGE_PROXY_REQUEST_ERROR);

            throw $e;
        }
        finally
        {
            $this->trace->histogram(self::METRIC_REQUEST_LATENCY_MS, millitime() - $requeststartAt);
        }

        return $proxyResponse;
    }

    /**
     * @param string $path
     * @param string $prefixTrim
     * @param string $prefixAdd
     * @return string
     */
    protected function getPath($path, $prefixTrim, $prefixAdd)
    {
        // removes the $prefixTrim from the path prefix
        if (substr($path, 0, strlen($prefixTrim)) == $prefixTrim)
        {
            $path = substr($path, strlen($prefixTrim));
        }

        $path = $prefixAdd . $path;

        return $path;
    }

    protected function getContent($contentType, Request $request){
        return $request->getContent();
    }
}
