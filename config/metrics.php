<?php

/**
 * Configurations consumed by Services\Metrics module
 */
return [
    // Default driver to use. Possible values: mock, prometheus
    'default'    => env('METRICS_DEFAULT_DRIVER'),

    // Configurations per driver
    'drivers'    => [
        'mock'       => [],

        'newrelic'   => [],

        'prometheus' => [
            'adapter'     => env('METRICS_PROMETHEUS_ADAPTER'),
            'pushgateway' => env('METRICS_PROMETHEUS_PUSHGATEWAY'),
        ],
    ],
];
