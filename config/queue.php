<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Driver
    |--------------------------------------------------------------------------
    |
    | The Laravel queue API supports a variety of back-ends via an unified
    | API, giving you convenient access to each back-end using the same
    | syntax for each one. Here you may set the default queue driver.
    |
    | Supported: "null", "sync", "database", "beanstalkd",
    |            "sqs", "redis"
    |
    */

    'default'               => env('QUEUE_DRIVER', 'sync'),

    'mock'                  => env('QUEUE_MOCK', false),

    /*
    |--------------------------------------------------------------------------
    | For accessing values via dot notation nested array need to created
    |--------------------------------------------------------------------------
    */
    'webhook' => [
        'connection' => 'sqs_multi_default',
        'test' => [
            'payment' => [
                'authorized'    => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'captured'      => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'failed'        => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'order' => [
                'paid'          => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'invoice' => [
                'paid'          => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'vpa' => [
                'edited'        => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'p2p' => [
                'created'       => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'rejected'      => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'transferred'   => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'subscription' => [
                'activated'     => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'pending'       => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'halted'        => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'expired'       => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'cancelled'     => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'completed'     => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ]
        ],
        'live' => [
            'payment' => [
                'authorized'    => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'captured'      => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'failed'        => env('AWS_WEBHOOKS_FAILURE_QUEUE'),
            ],
            'order' => [
                'paid'          => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'invoice' => [
                'paid'          => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'vpa' => [
                'edited'        => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'p2p' => [
                'created'       => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'rejected'      => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'transferred'   => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'subscription' => [
                'activated'     => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'pending'       => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'halted'        => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'expired'       => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'cancelled'     => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'completed'     => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ]
        ],
    ],

    'dashboard' => [
        'connection' => 'sqs_multi_default',
        'test'       => env('AWS_GENERAL_TEST_QUEUE'),
        'live'       => env('AWS_GENERAL_LIVE_QUEUE'),
    ],

    'es' => [
        'connection' => 'sqs_multi_default',
        'test'       => env('AWS_GENERAL_TEST_QUEUE'),
        'live'       => env('AWS_GENERAL_LIVE_QUEUE'),
    ],

    'es_v2' => [
        'connection' => 'sqs_multi_default',
        'test'       => env('AWS_ES_SYNC_QUEUE'),
        'live'       => env('AWS_ES_SYNC_QUEUE'),
    ],

    'reports' => [
        'connection' => 'sqs_multi_default',
        'test'       => env('AWS_REPORTS_QUEUE'),
        'live'       => env('AWS_REPORTS_QUEUE'),
    ],

    'merchant_invoice' => [
        'connection' => 'sqs_multi_default',
        'test'       => env('AWS_GENERAL_TEST_QUEUE'),
        'live'       => env('AWS_GENERAL_LIVE_QUEUE'),
    ],

    'invoice' => [
        'connection' => 'sqs_multi_default',
        'test'       => env('AWS_INVOICE_EMAILS_QUEUE'),
        'live'       => env('AWS_INVOICE_EMAILS_QUEUE'),
    ],

    'mail' => [
        'connection' => 'sqs_mail',
    ],

    'batch' => [
        'connection' => 'sqs_multi_default',
        'test'       => env('AWS_INVOICE_EMAILS_QUEUE'),
        'live'       => env('AWS_INVOICE_EMAILS_QUEUE'),
    ],

    'capture' => [
        'connection' => 'sqs_multi_default',
        'test'       => env('AWS_GENERAL_TEST_QUEUE'),
        'live'       => env('AWS_GENERAL_LIVE_QUEUE'),
    ],

    'gateway_file' => [
        'connection' => 'sqs_multi_default',
        'test'       => env('AWS_GENERAL_TEST_QUEUE'),
        'live'       => env('AWS_GENERAL_LIVE_QUEUE'),
    ],

    'sqs_general_live'      => env('AWS_GENERAL_LIVE_QUEUE'),
    'sqs_general_test'      => env('AWS_GENERAL_TEST_QUEUE'),
    'sqs_general_failure'   => env('AWS_GENERAL_FAILURE_QUEUE'),
    'sqs_webhooks_live'     => env('AWS_WEBHOOKS_LIVE_QUEUE'),
    'sqs_webhooks_test'     => env('AWS_WEBHOOKS_TEST_QUEUE'),
    'sqs_webhooks_failure'  => env('AWS_WEBHOOKS_FAILURE_QUEUE'),


    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Laravel. You are free to add more.
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table'  => 'jobs',
            'queue'  => 'default',
            'expire' => 60,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host'   => 'localhost',
            'queue'  => 'default',
            'ttr'    => 60,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key'    => env('AWS_KEY_ID'),
            'secret' => env('AWS_KEY_SECRET'),
            'prefix' => env('AWS_QUEUE_URL'),
            'queue'  => env('AWS_QUEUE_NAME'),
            'region' => env('AWS_REGION'),
        ],

        'sqs_multi_default' => [
            'driver' => 'sqs',
            'key'    => env('AWS_KEY_ID'),
            'secret' => env('AWS_KEY_SECRET'),
            'prefix' => env('AWS_QUEUE_PREFIX'),
            'queue'  => env('AWS_GENERAL_LIVE_QUEUE'),
            'region' => env('AWS_REGION'),
        ],

        'sqs_mail'  => [
            'driver' => 'sqs',
            'key'    => env('AWS_KEY_ID'),
            'secret' => env('AWS_KEY_SECRET'),
            'prefix' => env('AWS_QUEUE_PREFIX'),
            'queue'  => env('AWS_EMAILS_QUEUE'),
            'region' => env('AWS_REGION'),
        ],

        'redis' => [
            'driver'     => 'redis',
            'connection' => 'default',
            'queue'      => 'default',
            'expire'     => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table'    => 'failed_jobs',
    ],

];
