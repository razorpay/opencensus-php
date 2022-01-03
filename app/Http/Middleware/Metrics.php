<?php


namespace App\Http\Middleware;

use App\Http\ApiUrl;
use App\Metrics\Constants;
use App\Trace\TraceCode;
use Closure;
use Route;

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
            $this->app['trace']->warn(TraceCode::PUSH_METRICS_FAILED, [
                'message' => $throwable->getMessage() ?? 'unknown_message',
            ]);
        }
    }

    protected function getMetricDimensions($request, $response)
    {
        return [
            Constants::LABEL_HTTP_REQUESTS_PRODUCT     => ApiUrl::isBankingOriginRequest() ? Constants::BANKING : Constants::PRIMARY ,
            Constants::LABEL_HTTP_REQUESTS_METHOD      => $request->getMethod()                         ?? 'unknown_method',
            Constants::LABEL_HTTP_REQUESTS_STATUS      => $this->getStatusCode($response),
            Constants::LABEL_HTTP_REQUESTS_ROUTE       => $request->route() !== null ? $request->route()->getName() : 'unknown_route',
            Constants::LABEL_HTTP_REQUESTS_CONTROLLER  => $request->route() !== null ? $request->route()->getAction()['controller']  : 'unknown_controller',
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
