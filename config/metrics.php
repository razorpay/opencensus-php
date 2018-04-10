<?php

/**
 * Configurations consumed by Services\Metrics module
 */
return [
    // Default driver to use. Possible values: mock, dogstatsd
    'default'    => env('METRICS_DEFAULT_DRIVER'),

    'namespace'  => 'api',

    // Configurations per driver
    'drivers'    => [
        'mock'       => [],

        'dogstatsd' => [],
    ],
];
