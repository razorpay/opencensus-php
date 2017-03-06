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

    'sqs_general_test'      => env('AWS_GENERAL_TEST_QUEUE'),
    'sqs_general_failure'   => env('AWS_GENERAL_FAILURE_QUEUE'),
    'sqs_webhooks_live'     => env('AWS_WEBHOOK_LIVE_QUEUE'),
    'sqs_webhooks_test'     => env('AWS_WEBHOOK_TEST_QUEUE'),
    'sqs_webhooks_failure'  => env('AWS_WEBHOOK_FAILURE_QUEUE'),
    'sqs_invoice_emails'    => env('AWS_INVOICE_EMAILS_QUEUE'),


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
