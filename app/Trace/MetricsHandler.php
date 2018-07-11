<?php

namespace RZP\Trace;

use Metrics;
use Request;
use Monolog\Handler\AbstractProcessingHandler;

use RZP\Constants\Metric;

/**
 * A handler to be pushed in trace instance. This enables pushing metrics about tracing & exceptions.
 */
class MetricsHandler extends AbstractProcessingHandler
{
    /**
     * Following attributes of log record is used for metric dimension. It is an dot notation list to pull out specific
     * keys(nested) from trace record & then get used as metric dimensions.
     */
    const METRIC_DIMENSION_KEYS = [
        'channel',
        'code',
        'level',
        'level_name',
        'mode',
        'context.code',
        'request.merchant_id',
    ];

    /**
     * {@inheritDoc}
     */
    protected function write(array $record)
    {

        $filtered = array_only(array_dot($record), self::METRIC_DIMENSION_KEYS);

        $dimensions[Metric::LABEL_TRACE_CHANNEL]      = $filtered['channel'];
        $dimensions[Metric::LABEL_TRACE_CODE]         = $filtered['code'];
        $dimensions[Metric::LABEL_TRACE_LEVEL]        = $filtered['level'];
        $dimensions[Metric::LABEL_TRACE_LEVEL_NAME]   = $filtered['level_name'];
        $dimensions[Metric::LABEL_RZP_MODE]           = $filtered['mode'];
        //
        // Multiple times actual exception is wrapped in general exception e.g. RECOVERABLE_EXCEPTION. Following is the
        // underlying exception/trace code.
        //
        $dimensions[Metric::LABEL_TRACE_CONTEXT_CODE] = strval($filtered['context.code'] ?? $filtered['code']);
        $dimensions[Metric::LABEL_RZP_MERCHANT_ID]    = $filtered['request.merchant_id'];

        // Adds request route name, using optional() because async job wont' have a route instance
        $dimensions[Metric::LABEL_ROUTE] = optional(Request::route())->getName();

        Metrics::count(Metric::TRACES_TOTAL, 1, $dimensions);
    }
}
