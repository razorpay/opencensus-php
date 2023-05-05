<?php

namespace RZP\Http\Middleware;

use App;
use RZP\Http\RouteLatencyGroup;
use RZP\Http\RouteMaxLatency;
use RZP\Trace\TraceCode;
use RZP\Http\RouteTeamMap;
use Illuminate\Http\Request;
use Razorpay\Edge\Passport\Passport;
use Illuminate\Foundation\Application;
use Symfony\Component\HttpFoundation\Response;

use RZP\Constants\Metric;
use RZP\Http\Throttle\Throttler;
use RZP\Http\BasicAuth\BasicAuth;

final class Throttle
{
    protected $app;

    protected $trace;

    const API_HOST_COOKIE_KEY = 'rzp_api_host';

    public function __construct()
    {
        $this->app = $app = App::getFacadeRoot();;

        $this->trace = $this->app['trace'];
    }

    /**
     * Handles http request:
     * - Inits request context
     * - Attempts throttling
     * - Pushes http related metrics
     *
     * We are intentionally not adding another middleares to init reqeust context
     * & to push http metrics because middlewares in laravel have little overhead.
     *
     * @param  Request  $request
     * @param  \Closure $next
     * @return Response
     * @throws \Throwable
     */
    public function handle($request, \Closure $next)
    {
        app('request.ctx')->resolveKeyIdIfApplicable();

        (new Throttler)->throttle();

        return $next($request);
    }

    /**
     * Pushes specific HTTP metrics
     * @param  Request  $request
     * @param  Response $response
     * @param  int      $duration
     */
    public function pushHttpMetrics(Request $request, Response $response, int $duration)
    {
        $reqSize = strlen($request);
        $responseSize = strlen($response);
        $dimensions = $this->getMetricDimensions($request, $response);
        $importantDimensions = $this->getImportantMetricDimensions($request, $response);

        app('trace')->count(Metric::HTTP_REQUESTS_TOTAL, $dimensions);
        app('trace')->histogram(Metric::HTTP_REQUEST_DURATION_MILLISECONDS, $duration, $dimensions);
        app('trace')->histogram(Metric::HTTP_REQUEST_LATENCY_MILLISECONDS, $duration, $importantDimensions);
        app('trace')->histogram(Metric::HTTP_REQUEST_SIZE, $reqSize, $importantDimensions);
        app('trace')->histogram(Metric::HTTP_RESPONSE_SIZE, $responseSize, $importantDimensions);
    }

    /**
     * Gets dimensions/labels for HTTP metrics
     * @param  Request  $request
     * @param  Response $response
     * @return array
     */
    protected function getMetricDimensions(Request $request, Response $response): array
    {
        $requestCtx = app('request.ctx');
        /** @var BasicAuth $basicAuth */
        $basicAuth  = app('basicauth');

        return [
            Metric::LABEL_METHOD                => $request->getMethod(),
            Metric::LABEL_ROUTE                 => $request->route()->getName(),
            Metric::LABEL_STATUS                => $response->getStatusCode(),
            Metric::LABEL_RZP_MODE              => $requestCtx->getMode() ?: Metric::LABEL_NONE_VALUE,
            Metric::LABEL_RZP_KEY_ID            => $requestCtx->getKeyId() ?: Metric::LABEL_NONE_VALUE,
            Metric::LABEL_RZP_MERCHANT_ID       => $requestCtx->getMid() ?: Metric::LABEL_NONE_VALUE,
            Metric::LABEL_RZP_OAUTH_CLIENT_ID   => $requestCtx->getOAuthClientId() ?: Metric::LABEL_NONE_VALUE,
            Metric::LABEL_RZP_AUTH              => $requestCtx->getAuth(),
            Metric::LABEL_RZP_AUTH_FLOW_TYPE    => $requestCtx->getAuthFlowType(),
            Metric::LABEL_RZP_KEY_SOURCE        => $requestCtx->getKeySource() ?: Metric::LABEL_NONE_VALUE,
            Metric::LABEL_RZP_INTERNAL_APP_NAME => $requestCtx->getInternalAppName() ?: Metric::LABEL_NONE_VALUE,
            Metric::LABEL_HAS_PASSPORT          => $request->headers->has(Passport::PASSPORT_JWT_V1),
            Metric::LABEL_RZP_PRODUCT           => optional($basicAuth)->getProduct(), // optional because not sure basicAuth is initialized in all flows
            Metric::LABEL_RZP_TEAM              => RouteTeamMap::getTeamNamesForRoute($request->route()->getName()),
            Metric::LABEL_HOST                  => $request->getHttpHost() ?? Metric::LABEL_NONE_VALUE,
            Metric::LABEL_RZP_LATENCY_GROUP     => RouteLatencyGroup::getLatencyGroupForRoute($request->route()->getName()),
        ];
    }

    /**
     * Gets basic dimensions/labels for HTTP metrics
     * @param  Request  $request
     * @param  Response $response
     * @return array
     */
    protected function getImportantMetricDimensions(Request $request, Response $response): array
    {
        $requestCtx = app('request.ctx');

        return [
            Metric::LABEL_ROUTE                 => $request->route()->getName(),
            Metric::LABEL_STATUS                => $response->getStatusCode(),
            Metric::LABEL_RZP_MODE              => $requestCtx->getMode() ?: Metric::LABEL_NONE_VALUE,
        ];
    }
}
