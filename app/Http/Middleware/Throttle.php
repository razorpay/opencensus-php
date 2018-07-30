<?php

namespace RZP\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use RZP\Constants\Metric;
use RZP\Http\Throttle\Throttler;

final class Throttle
{
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
     */
    public function handle($request, \Closure $next)
    {
        $start = millitime();

        app('request.ctx')->init();

        (new Throttler)->throttle();

        $response = $next($request);

        $duration = millitime() - $start; // For metric http_request_duration_milliseconds, in milliseconds

        $this->pushHttpMetrics($request, $response, $duration);

        return $response;
    }

    /**
     * Pushes specific HTTP metrics
     * @param  Request  $request
     * @param  Response $response
     * @param  int      $duration
     */
    protected function pushHttpMetrics(Request $request, Response $response, int $duration)
    {
        $dimensions = $this->getMetricDimensions($request, $response);

        app('trace')->count(Metric::HTTP_REQUESTS_TOTAL, 1, $dimensions);
        app('trace')->histogram(Metric::HTTP_REQUEST_DURATION_MILLISECONDS, $duration, $dimensions);
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

        return [
            Metric::LABEL_METHOD                => $request->getMethod(),
            Metric::LABEL_ROUTE                 => $request->route()->getName(),
            Metric::LABEL_STATUS                => $response->getStatusCode(),
            Metric::LABEL_RZP_MODE              => $requestCtx->getMode() ?: Metric::LABEL_NONE_VALUE,
            Metric::LABEL_RZP_KEY_ID            => $requestCtx->getKeyId() ?: Metric::LABEL_NONE_VALUE,
            Metric::LABEL_RZP_MERCHANT_ID       => $requestCtx->getMid() ?: Metric::LABEL_NONE_VALUE,
            Metric::LABEL_RZP_OAUTH_CLIENT_ID   => $requestCtx->getOAuthClientId() ?: Metric::LABEL_NONE_VALUE,
            Metric::LABEL_RZP_AUTH              => $requestCtx->getAuth(),
            Metric::LABEL_RZP_INTERNAL_APP_NAME => $requestCtx->getInternalAppName() ?: Metric::LABEL_NONE_VALUE,
        ];
    }
}
