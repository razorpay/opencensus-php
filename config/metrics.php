<?php

use RZP\Constants\Metric;
use RZP\Trace\Metrics\DimensionsProcessor;

return [

    'processors' => [
        DimensionsProcessor::class,
    ],

    /*
    | Label Values Whitelist: In case some label has values of high cardinality
    | we can white list here a finite set of values for which we want specific
    | monitoring. For values not in the corresponding whitelist 'other' value
    | would be used.
    */
    'whitelisted_label_values' => [
        Metric::LABEL_RZP_KEY_ID          => [
        ],

        Metric::LABEL_RZP_MERCHANT_ID     => [
        ],

        Metric::LABEL_RZP_OAUTH_CLIENT_ID => [
        ],
    ],

    'default_label_value' => Metric::LABEL_DEFAULT_VALUE,

    /*
    |--------------------------------------------------------------------------
    | Configurations per driver
    |--------------------------------------------------------------------------
    |
    */
    'drivers'    => [
        'mock'      => [
            'impl' => \Razorpay\Metrics\Drivers\Mock::class,
        ],

        'dogstatsd_default' => [
            'impl' => \Razorpay\Metrics\Drivers\Dogstatsd::class,
            'client' => [
                'host'              => env('METRICS_DOGSTATSD_HOST'),
                'port'              => env('METRICS_DOGSTATSD_PORT'),
                'disable_telemetry' => env('METRICS_DOGSTATSD_DISABLE_TELEMETRY'),
            ],
        ],

        'dogstatsd_gateway' => [
            'impl' => \Razorpay\Metrics\Drivers\Dogstatsd::class,
            'client' => [
                'host'              => env('GATEWAY_METRICS_DOGSTATSD_HOST'),
                'port'              => env('GATEWAY_METRICS_DOGSTATSD_PORT'),
                'disable_telemetry' => env('METRICS_DOGSTATSD_DISABLE_TELEMETRY'),
            ],
        ],
    ],
];
