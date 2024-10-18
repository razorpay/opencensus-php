<?php

namespace RZP\Services\Route;

use App;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

final class Metric
{
    use Base\Traits\MetricTrait;

    const ROUTE_NAME    = 'route_name';
    const CONTEXT_NAME  = 'context_name';
    const RESPONSE_CODE = 'response_code';
    const REQUEST_METRICS = 'route_service_request_metrics';

    public function pushRequestMetrics($responseCode, \Throwable $ex=null)
    {
        $dimensions = [
            self::ROUTE_NAME                  => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,3)[2]['function'],
            self::CONTEXT_NAME                => $this->getContextName(),
            self::RESPONSE_CODE               => $responseCode,
        ];

        if (empty($ex) === false)
        {
            $exceptionDimensions = $this->getDefaultExceptionDimensions($ex);

            $dimensions = array_merge($dimensions, $exceptionDimensions);
        }

        $trace = App::getFacadeRoot()['trace'];

        $trace->count(self::REQUEST_METRICS, $dimensions);
    }

    private function getContextName()
    {
        $app = App::getFacadeRoot();

        if ($app->runningInQueue() === true)
        {
            $workerName = $app['worker.ctx']?->getJobName();

            return $workerName;
        }

        $routeName = $app['api.route']?->getCurrentRouteName();

        return $routeName;
    }
}
