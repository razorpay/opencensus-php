<?php


namespace App\Http\Middleware;

use Route;
use Closure;
use App\Http\ApiUrl;
use App\Trace\TraceCode;
use App\Metrics\Constants;
use App\Http\RouteTeamMap;


class Metrics
{
    protected $app;
    /**
     * Metrics constructor.
     */
    public function __construct()
    {
        $this->app = \App::getFacadeRoot();
    }

    public function handle($request, Closure $next)
    {
        $start = self::millitime();

        $response = $next($request);

        $duration = self::millitime() - $start;

        $this->pushMetrics($duration, $request, $response);

        return $response;
    }

    protected function pushMetrics(int $duration, $request, $response)
    {
        try
        {
            $dimensions = $this->getMetricDimensions($request, $response);

            $this->app['metrics']->count(Constants::METRIC_COUNTER_HTTP_REQUESTS, Constants::EVENT_COUNT_ONE, $dimensions);

            $this->app['metrics']->histogram(Constants::METRIC_HISTOGRAM_HTTP_REQUESTS_DURATION, $duration, $dimensions);
        }
        catch (\Throwable $throwable)
        {
            $this->app['trace']->warning(TraceCode::PUSH_METRICS_FAILED, [
                'message' => $throwable->getMessage() ?? 'unknown_message',
            ]);
        }
    }

    protected function getMetricDimensions($request, $response)
    {
        $routeName = $request->route() !== null ? $request->route()->getName() : 'unknown_route';

        $apolloClientName = $request->header('apollographql-client-name');

        $teamByRoute = RouteTeamMap::getTeamNamesForRoute($routeName);

        $tagByTeam = RouteTeamMap::getTeamSlackTag($teamByRoute);

        return [
            Constants::LABEL_HTTP_REQUESTS_ORIGIN         => ApiUrl::getRequestOrigin(),
            Constants::LABEL_HTTP_REQUESTS_DOMAIN         => $request->server->get('SERVER_NAME') ?? 'unknown_domain',
            Constants::LABEL_HTTP_REQUESTS_GRAPHQL_CLIENT => $apolloClientName ?? 'unknown_graphql_client',
            Constants::LABEL_HTTP_REQUESTS_PRODUCT        => ApiUrl::isBankingOriginRequest() ? Constants::BANKING : Constants::PRIMARY ,
            Constants::LABEL_HTTP_REQUESTS_METHOD         => $request->getMethod() ?? 'unknown_method',
            Constants::LABEL_HTTP_REQUESTS_ROUTE          => $routeName,
            Constants::LABEL_HTTP_REQUESTS_STATUS         => $this->getStatusCode($response),
            Constants::LABEL_HTTP_REQUESTS_CONTROLLER     => $request->route() !== null ? $request->route()->getAction()['controller']  : 'unknown_controller',
            Constants::LABEL_RZP_TEAM                     => $teamByRoute,
            Constants::LABEL_RZP_TEAM_TAG                 => $tagByTeam,
        ];
    }

    protected function getStatusCode($response)
    {
        $data = method_exists($response, 'getData') ? $response->getData() : null;

        if (isset($data->http_status_code) === true)
        {
           return $data->http_status_code;
        }

        return  $response->getStatusCode() ?? 'unknown_status';
    }

    static function millitime(): int
    {
        return round(microtime(true) * 1000);
    }
}
