<?php

namespace RZP\Services\Route;

use App;
use RZP\Models\Base;

final class Metric
{
    use Base\Traits\MetricTrait;

    const ROUTE_NAME    = 'route_name';
    const RESPONSE_CODE = 'response_code';
    const REQUEST_METRICS = 'route_service_request_metrics';

    public function pushRequestMetrics($responseCode, \Throwable $ex=null)
    {
        $dimensions = [
            self::ROUTE_NAME                  => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['function'],
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
}
