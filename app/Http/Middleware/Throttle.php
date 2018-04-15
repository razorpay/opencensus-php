<?php

namespace RZP\Http\Middleware;

use Metrics;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use RZP\Constants\Metric;
use RZP\Http\Throttle\Throttler;

final class Throttle
{
    public function handle($request, \Closure $next)
    {
        $start = microtime(true);

        (new Throttler)->throttle($request);

        $response = $next($request);

        $this->pushHttpMetrics($request, $response, $start);

        return $response;
    }

    /**
     * Pushes specific HTTP metrics
     * @param  Request  $request
     * @param  Response $response
     * @param  int      $start
     */
    protected function pushHttpMetrics(Request $request, Response $response, int $start)
    {
        $dimensions = $this->getMetricDimensions($request, $response);

        Metrics::count(Metric::HTTP_REQUESTS_TOTAL, 1, $dimensions);
        Metrics::histogram(Metric::HTTP_REQUEST_DURATION_MICROSECONDS, microtime(true) - $start, [], $dimensions);
        Metrics::histogram(Metric::HTTP_REQUEST_SIZE_BYTES, mb_strlen($request->getContent()), [], $dimensions);
        Metrics::histogram(Metric::HTTP_RESPONSE_SIZE_BYTES, mb_strlen($response->getContent()), [], $dimensions);
    }

    /**
     * Gets dimensions/labels for HTTP metrics
     * @param  Request  $request
     * @param  Response $response
     * @return array
     */
    protected function getMetricDimensions(Request $request, Response $response): array
    {
        //
        // Todo:
        // Awaiting an ongoing refactoring effort to move request's context variables (e.g. merchant identifier, internal
        // application name etc.) out of any existing tied class (e.g. BasicAuth) so can be used at various places. Past
        // that should move this part of code elsewhere. Currently keeping here as this is the first one to get triggered.
        //

        //
        // Enhancement:
        // We would like to tag metrics for specific key merchants at leasts. For this need someway to know when to use
        // the merchant id in label value and when to use a placeholder. TBD later. Also awaiting on above refactoring(
        // todo ^) so this could be done in a clean way.
        //

        return [
            Metric::LABEL_METHOD                => $request->getMethod(),
            Metric::LABEL_ROUTE                 => $request->route()->getName(),
            Metric::LABEL_STATUS                => $response->getStatusCode(),
            Metric::LABEL_RZP_MODE              => Metric::LABEL_DEFAULT_VALUE,
            Metric::LABEL_RZP_KEY_ID            => Metric::LABEL_DEFAULT_VALUE,
            Metric::LABEL_RZP_MERCHANT_ID       => Metric::LABEL_DEFAULT_VALUE,
            Metric::LABEL_RZP_OAUTH_CLIENT_ID   => Metric::LABEL_DEFAULT_VALUE,
            Metric::LABEL_RZP_AUTH              => Metric::LABEL_DEFAULT_VALUE,
            Metric::LABEL_RZP_INTERNAL_APP_NAME => Metric::LABEL_DEFAULT_VALUE,
        ];
    }
}
