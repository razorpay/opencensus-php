<?php

use RZP\Constants\Metric;

/**
 * Configurations consumed by Services\Metrics module
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Default Driver
    | Possible values: mock, dogstatsd
    |--------------------------------------------------------------------------
    |
    */
    'default'    => env('METRICS_DEFAULT_DRIVER'),

    /*
    |--------------------------------------------------------------------------
    | Metrics namespace: Used as prefix to metric names
    |--------------------------------------------------------------------------
    |
    */
    'namespace'  => 'api',

    /*
    |--------------------------------------------------------------------------
    | Configurations per driver
    |--------------------------------------------------------------------------
    |
    */
    'drivers'    => [
        'mock'      => [],

        'dogstatsd' => [

            // Client options
            'client' => [
                'host' => env('METRICS_DOGSTATSD_HOST'),
                'port' => env('METRICS_DOGSTATSD_PORT'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Label Values Whitelist: In case some label has values of high cardinality
    | we can white list here a finite set of values for which we want specific
    | monitoring. For values not in the corresponding whitelist 'other' value
    | would be used.
    |--------------------------------------------------------------------------
    |
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
];
