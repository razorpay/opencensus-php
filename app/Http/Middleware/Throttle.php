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

        $throttler = new Throttler;

        $throttler->throttle($request);

        $response = $next($request);

        $this->pushHttpMetrics($throttler, $request, $response, $start);

        return $response;
    }

    //
    // Todo:
    // Awaiting an ongoing refactoring effort to move request's context out of any existing tied class(e.g. BasicAuth)
    // so can be used by various middlewares. Past that I should move this part of code elsewhere. Currently keeping in
    // throttler middleware as this is the first one to get triggered.
    //

    /**
     * Pushes specific HTTP metrics
     * @param  Throttler $throttler
     * @param  Request   $request
     * @param  Response  $response
     * @param  int       $start
     */
    protected function pushHttpMetrics(Throttler $throttler, Request $request, Response $response, int $start)
    {
        $dimensions = $this->getMetricDimensions($throttler, $request, $response);

        Metrics::count(Metric::HTTP_REQUESTS_TOTAL, 1, $dimensions);
        Metrics::histogram(Metric::HTTP_REQUEST_DURATION_MICROSECONDS, microtime(true) - $start, [], $dimensions);
        Metrics::histogram(Metric::HTTP_REQUEST_SIZE_BYTES, mb_strlen($request->getContent()), [], $dimensions);
        Metrics::histogram(Metric::HTTP_RESPONSE_SIZE_BYTES, mb_strlen($response->getContent()), [], $dimensions);
    }

    /**
     * Gets dimensions/labels for HTTP metrics
     * @param  Throttler $throttler
     * @param  Request   $request
     * @param  Response  $response
     * @return array
     */
    protected function getMetricDimensions(Throttler $throttler, Request $request, Response $response): array
    {
        //
        // Enhancement:
        // We would like to tag metrics for specific key merchants at leasts. For this need someway to know when to use
        // the merchant id in label value and when to use a placeholder. TBD later. Also awaiting on above refactoring(
        // todo ^) so this could be done in a clean way.
        //

        $mode            = $throttler->getMode() ?: Metric::LABEL_DEFAULT_VALUE;
        $status          = $response->getStatusCode() ?: Metric::LABEL_DEFAULT_VALUE;
        $method          = $request->getMethod() ?: Metric::LABEL_DEFAULT_VALUE;
        $keyId           = $throttler->getKeyId() ?: Metric::LABEL_DEFAULT_VALUE;
        $merchantId      = Metric::LABEL_DEFAULT_VALUE;
        $oauthClientId   = Metric::LABEL_DEFAULT_VALUE;
        $auth            = $throttler->getAuth() ?: Metric::LABEL_DEFAULT_VALUE;
        $internalAppName = $throttler->getInternalAppNameAttribute() ?: Metric::LABEL_DEFAULT_VALUE;

        return [
            Metric::LABEL_RZP_MODE              => $mode,
            Metric::LABEL_STATUS                => $status,
            Metric::LABEL_METHOD                => $method,
            Metric::LABEL_RZP_KEY_ID            => $keyId,
            Metric::LABEL_RZP_MERCHANT_ID       => $merchantId,
            Metric::LABEL_RZP_OAUTH_CLIENT_ID   => $oauthClientId,
            Metric::LABEL_RZP_AUTH              => $auth,
            Metric::LABEL_RZP_INTERNAL_APP_NAME => $internalAppName,
        ];
    }
}
