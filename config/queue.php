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

    /*
    | If set to true (only in local/testing environment), all queue jobs are pushed to default connection & queue.
    | Only applies for asynchronous job drivers.
    */
    'routing_mock'            => env('QUEUE_ROUTING_MOCK', false),

    /*
    |--------------------------------------------------------------------------
    | Contains mapping of route keys(nested) and which queue connection & queue name to use respectively.
    | Usage: Ref \RZP\Jobs\Extended\PendingDispatch.php
    |--------------------------------------------------------------------------
    */

    'webhook' => [
        'test' => [
            'payment' => [
                'authorized'        => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'captured'          => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'failed'            => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'dispute' => [
                    'created'       => env('AWS_WEBHOOKS_TEST_QUEUE'),
                ],
            ],
            'order' => [
                'paid'              => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'invoice' => [
                'paid'              => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'partially_paid'    => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'expired'           => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'vpa' => [
                'edited'            => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'p2p' => [
                'created'           => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'rejected'          => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'transferred'       => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'subscription' => [
                'activated'         => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'charged'           => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'pending'           => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'halted'            => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'expired'           => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'cancelled'         => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'completed'         => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'settlement' => [
                'processed'         => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'virtual_account' => [
                'created'           => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'credited'          => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'token' => [
                'confirmed'         => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
        ],
        'live' => [
            'payment' => [
                'authorized'        => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'captured'          => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'failed'            => env('AWS_WEBHOOKS_FAILURE_QUEUE'),
                'dispute' => [
                    'created'       => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                ],
            ],
            'order' => [
                'paid'              => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'invoice' => [
                'paid'              => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'partially_paid'    => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'expired'           => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'vpa' => [
                'edited'            => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'p2p' => [
                'created'           => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'rejected'          => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'transferred'       => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'subscription' => [
                'activated'         => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'charged'           => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'pending'           => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'halted'            => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'expired'           => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'cancelled'         => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'completed'         => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'settlement' => [
                'processed'         => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'virtual_account' => [
                'created'           => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'credited'          => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'token' => [
                'confirmed'         => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
        ],
    ],
    'dashboard' => [
        'test'       => env('AWS_GENERAL_TEST_QUEUE'),
        'live'       => env('AWS_GENERAL_LIVE_QUEUE'),
    ],
    'es_sync' => [
        'test'       => env('AWS_ES_SYNC_QUEUE'),
        'live'       => env('AWS_ES_SYNC_QUEUE'),
    ],
    'reports_job' => [
        'test'       => env('AWS_REPORTS_QUEUE'),
        'live'       => env('AWS_REPORTS_QUEUE'),
    ],
    'merchant_invoice' => [
        'test'       => env('AWS_INVOICE_REPORTS_QUEUE'),
        'live'       => env('AWS_INVOICE_REPORTS_QUEUE'),
    ],
    'invoice' => [
        'test'       => env('AWS_INVOICE_EMAILS_QUEUE'),
        'live'       => env('AWS_INVOICE_EMAILS_QUEUE'),
    ],
    'batch' => [
        'test'       => env('AWS_BATCH_QUEUE'),
        'live'       => env('AWS_BATCH_QUEUE'),
    ],
    'capture' => [
        'test'       => env('AWS_GENERAL_TEST_QUEUE'),
        'live'       => env('AWS_GENERAL_LIVE_QUEUE'),
    ],
    'gateway_file' => [
        'test'       => env('AWS_GENERAL_TEST_QUEUE'),
        'live'       => env('AWS_GENERAL_LIVE_QUEUE'),
    ],

    /*
     | Lists various queues to be used per mailable
     */
    'mail' => [
        'default' => env('AWS_EMAILS_QUEUE'),
    ],

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
            'prefix' => env('AWS_QUEUE_PREFIX'),
            'queue'  => env('AWS_DEFAULT_QUEUE'),
            'region' => env('AWS_REGION'),
        ],

        // TODO: Update brahma's & k8s code & remove this block
        // Ref: https://github.com/razorpay/brahma/blob/master/ansible-playbooks/roles/app-supervisor/templates/api.supervisor.conf.j2#L19
        'sqs_multi_default' => [
            'driver' => 'sqs',
            'key'    => env('AWS_KEY_ID'),
            'secret' => env('AWS_KEY_SECRET'),
            'prefix' => env('AWS_QUEUE_PREFIX'),
            'queue'  => env('AWS_DEFAULT_QUEUE'),
            'region' => env('AWS_REGION'),
        ],

        // TODO: Slack lib should expose method to set just queue name instead of connection
        'sqs_slack' => [
            'driver' => 'sqs',
            'key'    => env('AWS_KEY_ID'),
            'secret' => env('AWS_KEY_SECRET'),
            'prefix' => env('AWS_QUEUE_PREFIX'),
            'queue'  => env('AWS_GENERAL_LIVE_QUEUE'),
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
