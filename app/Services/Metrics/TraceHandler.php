<?php

namespace RZP\Services\Metrics;

use Request;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

use RZP\Constants\Metric;

/**
 * A handler to be pushed in our Trace package. This enables pushing metrics
 * about tracing & exceptions.
 */
class TraceHandler extends AbstractProcessingHandler
{
    /**
     * Following attributes of log record is pushed as dimension to metric
     */
    const RECORD_METRIC_DIMENSIONS = [
        Metric::LABEL_TRACE_CHANNEL,
        Metric::LABEL_TRACE_CODE,
        Metric::LABEL_TRACE_LEVEL,
        Metric::LABEL_TRACE_LEVEL_NAME,
    ];

    /**
     * {@inheritDoc}
     */
    protected function write(array $record)
    {
        $dimensions = array_only($record, self::RECORD_METRIC_DIMENSIONS);

        //
        // Adds api's route name & mode as well in list of dimensions
        // We use optional() method to get route name because in tests and async
        // job there won't be a current route() associated with Request.
        //
        $dimensions[Metric::LABEL_ROUTE]    = optional(Request::route())->getName();
        $dimensions[Metric::LABEL_RZP_MODE] = $record['mode'];

        \Metrics::count(Metric::TRACES_TOTAL, 1, $dimensions);
    }
}
