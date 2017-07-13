<?php

return array(

    /*
    |--------------------------------------------------------------------------
    | PDO Fetch Style
    |--------------------------------------------------------------------------
    |
    | By default, database results will be returned as instances of the PHP
    | stdClass object; however, you may desire to retrieve records in an
    | array format for simplicity. Here you can tweak the fetch style.
    |
    */

    'fetch' => PDO::FETCH_CLASS,

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => 'live',

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [
        'live' => [
            'driver'    => env('DB_LIVE_DRIVER'),
            'host'      => env('DB_LIVE_HOST'),
            'port'      => env('DB_LIVE_PORT'),
            'database'  => env('DB_LIVE_DATABASE'),
            'username'  => env('DB_LIVE_USERNAME'),
            'password'  => env('DB_LIVE_PASSWORD'),
            'charset'   => 'utf8',
            'collation' => 'utf8_bin',
            'prefix'    => '',
            'strict'    => true
        ],

        'test' => [
            'driver'    => env('DB_TEST_DRIVER'),
            'host'      => env('DB_TEST_HOST'),
            'port'      => env('DB_TEST_PORT'),
            'database'  => env('DB_TEST_DATABASE'),
            'username'  => env('DB_TEST_USERNAME'),
            'password'  => env('DB_TEST_PASSWORD'),
            'charset'   => 'utf8',
            'collation' => 'utf8_bin',
            'prefix'    => '',
            'strict'    => true
        ],

        'slave-live' => [
            // Slave must have the same driver and DB names as the master.
            'driver'    => env('DB_LIVE_DRIVER'),
            'host'      => env('SLAVE_DB_LIVE_HOST'),
            'port'      => env('SLAVE_DB_LIVE_PORT'),
            'database'  => env('DB_LIVE_DATABASE'),
            'username'  => env('SLAVE_DB_LIVE_USERNAME'),
            'password'  => env('SLAVE_DB_LIVE_PASSWORD'),
            'charset'   => 'utf8',
            'collation' => 'utf8_bin',
            'prefix'    => '',
            'strict'    => true
        ],

        'slave-test' => [
            'driver'    => env('DB_TEST_DRIVER'),
            'host'      => env('SLAVE_DB_TEST_HOST'),
            'port'      => env('SLAVE_DB_TEST_PORT'),
            'database'  => env('DB_TEST_DATABASE'),
            'username'  => env('SLAVE_DB_TEST_USERNAME'),
            'password'  => env('SLAVE_DB_TEST_PASSWORD'),
            'charset'   => 'utf8',
            'collation' => 'utf8_bin',
            'prefix'    => '',
            'strict'    => true
        ],

        'auth' => [
            'driver'    => env('DB_AUTH_DRIVER'),
            'host'      => env('DB_AUTH_HOST'),
            'port'      => env('DB_AUTH_PORT'),
            'database'  => env('DB_AUTH_DATABASE'),
            'username'  => env('DB_AUTH_USERNAME'),
            'password'  => env('DB_AUTH_PASSWORD'),
            'charset'   => 'utf8',
            'collation' => 'utf8_bin',
            'prefix'    => '',
            'strict'    => true
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => array(

        'cluster' => false,

        'default' => array(
            'host'                  => env('REDIS_HOST'),
            'port'                  => env('REDIS_PORT'),
            'database'              => env('REDIS_DB'),
            'timeout'               => 30,
        ),

        'secure' => array(
            'host'                  => env('SECURE_REDIS_HOST'),
            'port'                  => env('SECURE_REDIS_PORT'),
            'database'              => env('SECURE_REDIS_DB'),
            'timeout'               => 30,
        )
    ),

    /*
    |--------------------------------------------------------------------------
    | ElasticSearch
    |--------------------------------------------------------------------------
    */

    'es_audit' => [
        'live' => env('ES_AUDIT_LIVE_INDEX'),
        'test' => env('ES_AUDIT_TEST_INDEX')
    ],

    'es_workflow_action' => [
        'live' => env('ES_WORKFLOW_ACTION_LIVE_INDEX'),
        'test' => env('ES_WORKFLOW_ACTION_TEST_INDEX')
    ],

    'es_host'                 => env('ES_HOST'),

    'es_audit_host'           => env('ES_AUDIT_HOST'),

    'es_mock'                 => env('ES_MOCK'),

    'es_audit_mock'           => env('ES_AUDIT_MOCK'),

    'es_workflow_action_mock' => env('ES_WORKFLOW_ACTION_MOCK', false),

    'es_entity_index_prefix'  => env('ES_ENTITY_INDEX_PREFIX'),
);
