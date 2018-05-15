<?php

namespace RZP\Tests\Unit\Services\Metrics;

use RZP\Trace\TraceCode;
use RZP\Constants\Metric;

return [

    // Lists method arguments expectation for consecutive calls to Metrics service
    'testTracesAreSendingMetrics' => [
        [
            Metric::TRACES_TOTAL,
            1,
            [
                Metric::LABEL_TRACE_CHANNEL     => 'Razorpay API',
                Metric::LABEL_TRACE_CODE        => TraceCode::PAYMENT_NEW_REQUEST,
                Metric::LABEL_TRACE_LEVEL       => 200,
                Metric::LABEL_TRACE_LEVEL_NAME  => 'INFO',
                Metric::LABEL_ROUTE             => null,
                Metric::LABEL_RZP_MODE          => null,
            ],
        ],
        [
            Metric::TRACES_TOTAL,
            1,
            [
                Metric::LABEL_TRACE_CHANNEL     => 'Razorpay API',
                Metric::LABEL_TRACE_CODE        => TraceCode::PAYMENT_AUTH_FAILURE,
                Metric::LABEL_TRACE_LEVEL       => 500,
                Metric::LABEL_TRACE_LEVEL_NAME  => 'CRITICAL',
                Metric::LABEL_ROUTE             => null,
                Metric::LABEL_RZP_MODE          => null,
            ],
        ],
        [
            Metric::TRACES_TOTAL,
            1,
            [
                Metric::LABEL_TRACE_CHANNEL     => 'Razorpay API',
                Metric::LABEL_TRACE_CODE        => TraceCode::ERROR_EXCEPTION,
                Metric::LABEL_TRACE_LEVEL       => 400,
                Metric::LABEL_TRACE_LEVEL_NAME  => 'ERROR',
                Metric::LABEL_ROUTE             => null,
                Metric::LABEL_RZP_MODE          => null,
            ],
        ],
    ],
];
