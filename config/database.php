<?php

use RZP\Models\Admin\ConfigKey;

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
            'read'  => [
                'host'     => env('SLAVE_DB_LIVE_HOST'),
                'port'     => env('SLAVE_DB_LIVE_PORT'),
                'username' => env('SLAVE_DB_LIVE_USERNAME'),
                'password' => env('SLAVE_DB_LIVE_PASSWORD'),
            ],
            'write' => [
                'host'     => env('DB_LIVE_HOST'),
                'port'     => env('DB_LIVE_PORT'),
                'username' => env('DB_LIVE_USERNAME'),
                'password' => env('DB_LIVE_PASSWORD'),
            ],
            'sticky'    => true,
            'database'  => env('DB_LIVE_DATABASE'),
            'driver'    => env('DB_LIVE_DRIVER'),
            'charset'   => 'utf8',
            'collation' => 'utf8_bin',
            'prefix'    => '',
            'strict'    => true,
            'lag_check' => [
                'driver' => 'redis',
                'flag'   => ConfigKey::MASTER_PERCENT,
             ],
            'heartbeat_check' => [
                'driver'                => 'heartbeat',
                'force_run'             => ConfigKey::HEARTBEAT_FORCE_RUN,
                'enabled'               => ConfigKey::HEARTBEAT_ENABLED,
                'mock'                  => ConfigKey::HEARTBEAT_MOCK,
                'time_threshold'        => ConfigKey::HEARTBEAT_TIME_THRESHOLD,
                'slave_time_threshold'  => ConfigKey::HEARTBEAT_SLAVE_TIME_THRESHOLD,
                'routes'                => ConfigKey::HEARTBEAT_ROUTES,
                'traffic_percentage'    => ConfigKey::HEARTBEAT_TRAFFIC_PERCENTAGE,
                'log_verbose'           => ConfigKey::HEARTBEAT_LOG_VERBOSE,
            ],
            'wait_timeout'              => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout'  => env('DB_TRANSACTION_WAIT_TIMEOUT'),
        ],

        'live_migration' => [
            'host'      => env('DB_LIVE_HOST'),
            'port'      => env('DB_LIVE_PORT'),
            'username'  => env('DB_LIVE_MIGRATION_USERNAME'),
            'password'  => env('DB_LIVE_MIGRATION_PASSWORD'),
            'database'  => env('DB_LIVE_DATABASE'),
            'driver'    => env('DB_LIVE_DRIVER'),
            'charset'   => 'utf8',
            'collation' => 'utf8_bin',
            'prefix'    => '',
            'strict'    => true,
        ],

        'test' => [
            'read'  => [
                'host'     => env('SLAVE_DB_TEST_HOST'),
                'port'     => env('SLAVE_DB_TEST_PORT'),
                'username' => env('SLAVE_DB_TEST_USERNAME'),
                'password' => env('SLAVE_DB_TEST_PASSWORD'),
            ],
            'write' => [
                'host'     => env('DB_TEST_HOST'),
                'port'     => env('DB_TEST_PORT'),
                'username' => env('DB_TEST_USERNAME'),
                'password' => env('DB_TEST_PASSWORD'),
            ],
            'sticky'    => true,
            'database'  => env('DB_TEST_DATABASE'),
            'driver'    => env('DB_TEST_DRIVER'),
            'charset'   => 'utf8',
            'collation' => 'utf8_bin',
            'prefix'    => '',
            'strict'    => true,
            'lag_check' => [
                'driver' => 'redis',
                'flag'   => ConfigKey::MASTER_PERCENT,
             ],
            'heartbeat_check' => [
                'driver'                => 'heartbeat',
                'force_run'             => ConfigKey::HEARTBEAT_FORCE_RUN,
                'enabled'               => ConfigKey::HEARTBEAT_ENABLED,
                'mock'                  => ConfigKey::HEARTBEAT_MOCK,
                'time_threshold'        => ConfigKey::HEARTBEAT_TIME_THRESHOLD,
                'slave_time_threshold'  => ConfigKey::HEARTBEAT_SLAVE_TIME_THRESHOLD,
                'routes'                => ConfigKey::HEARTBEAT_ROUTES,
                'traffic_percentage'    => ConfigKey::HEARTBEAT_TRAFFIC_PERCENTAGE,
                'log_verbose'           => ConfigKey::HEARTBEAT_LOG_VERBOSE,
            ],
            'wait_timeout'              => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout'  => env('DB_TRANSACTION_WAIT_TIMEOUT'),
        ],

        'master-replica-test' => [
            'driver'    => env('DB_TEST_DRIVER'),
            'host'      => env('ES_DB_TEST_HOST'),
            'port'      => env('SLAVE_DB_TEST_PORT'),
            'database'  => env('DB_TEST_DATABASE'),
            'username'  => env('SLAVE_DB_TEST_USERNAME'),
            'password'  => env('SLAVE_DB_TEST_PASSWORD'),
            'charset'   => 'utf8',
            'collation' => 'utf8_bin',
            'prefix'    => '',
            'strict'    => true,
            'wait_timeout'              => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout'  => env('DB_TRANSACTION_WAIT_TIMEOUT'),
        ],

        'master-replica-live' => [
            'driver'    => env('DB_LIVE_DRIVER'),
            'host'      => env('ES_DB_LIVE_HOST'),
            'port'      => env('SLAVE_DB_LIVE_PORT'),
            'database'  => env('DB_LIVE_DATABASE'),
            'username'  => env('SLAVE_DB_LIVE_USERNAME'),
            'password'  => env('SLAVE_DB_LIVE_PASSWORD'),
            'charset'   => 'utf8',
            'collation' => 'utf8_bin',
            'prefix'    => '',
            'strict'    => true,
            'wait_timeout'              => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout'  => env('DB_TRANSACTION_WAIT_TIMEOUT'),
        ],

        'test_migration' => [
            'host'      => env('DB_TEST_HOST'),
            'port'      => env('DB_TEST_PORT'),
            'username'  => env('DB_TEST_MIGRATION_USERNAME'),
            'password'  => env('DB_TEST_MIGRATION_PASSWORD'),
            'database'  => env('DB_TEST_DATABASE'),
            'driver'    => env('DB_TEST_DRIVER'),
            'charset'   => 'utf8',
            'collation' => 'utf8_bin',
            'prefix'    => '',
            'strict'    => true,
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
            'strict'    => true,
            'wait_timeout'              => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout'  => env('DB_TRANSACTION_WAIT_TIMEOUT'),
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
            'strict'    => true,
            'wait_timeout'              => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout'  => env('DB_TRANSACTION_WAIT_TIMEOUT'),
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
            'strict'    => true,
            'wait_timeout'              => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout'  => env('DB_TRANSACTION_WAIT_TIMEOUT'),
        ],

        'payments_upi_live' => [
            'read'  => [
                'host'     => env('SLAVE_DB_UPI_PAYMENTS_LIVE_HOST'),
                'port'     => env('SLAVE_DB_UPI_PAYMENTS_LIVE_PORT'),
                'username' => env('SLAVE_DB_UPI_PAYMENTS_LIVE_USERNAME'),
                'password' => env('SLAVE_DB_UPI_PAYMENTS_LIVE_PASSWORD'),
            ],
            'write' => [
                'host'     => env('DB_UPI_PAYMENTS_LIVE_HOST'),
                'port'     => env('DB_UPI_PAYMENTS_LIVE_PORT'),
                'username' => env('DB_UPI_PAYMENTS_LIVE_USERNAME'),
                'password' => env('DB_UPI_PAYMENTS_LIVE_PASSWORD'),
            ],
            'sticky'    => true,
            'database'  => env('DB_UPI_PAYMENTS_LIVE_DATABASE'),
            'driver'    => env('DB_UPI_PAYMENTS_LIVE_DRIVER'),
            'charset'   => 'utf8',
            'collation' => 'utf8_bin',
            'prefix'    => '',
            'strict'    => true,
            'wait_timeout'              => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout'  => env('DB_TRANSACTION_WAIT_TIMEOUT'),
        ],

        'payments_upi_test' => [
            'read'  => [
                'host'     => env('SLAVE_DB_UPI_PAYMENTS_TEST_HOST'),
                'port'     => env('SLAVE_DB_UPI_PAYMENTS_TEST_PORT'),
                'username' => env('SLAVE_DB_UPI_PAYMENTS_TEST_USERNAME'),
                'password' => env('SLAVE_DB_UPI_PAYMENTS_TEST_PASSWORD'),
            ],
            'write' => [
                'host'     => env('DB_UPI_PAYMENTS_TEST_HOST'),
                'port'     => env('DB_UPI_PAYMENTS_TEST_PORT'),
                'username' => env('DB_UPI_PAYMENTS_TEST_USERNAME'),
                'password' => env('DB_UPI_PAYMENTS_TEST_PASSWORD'),
            ],
            'sticky'    => true,
            'database'  => env('DB_UPI_PAYMENTS_TEST_DATABASE'),
            'driver'    => env('DB_UPI_PAYMENTS_TEST_DRIVER'),
            'charset'   => 'utf8',
            'collation' => 'utf8_bin',
            'prefix'    => '',
            'strict'    => true,
            'wait_timeout'              => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout'  => env('DB_TRANSACTION_WAIT_TIMEOUT'),
        ],

        'proxy_sql_unix_socket' => env('PROXY_SQL_UNIX_SOCKET'),
        'proxy_sql_enable' => env('PROXY_SQL_ENABLE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | DB Query Timeout Config -- Milliseconds
    |--------------------------------------------------------------------------
    |
    | This is the query-timeout limit for all select queries. Value is in
    | milliseconds.
    */
    'db_mysql_query_timeout' => env('DB_MYSQL_QUERY_TIMEOUT', 900000),

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

    'redis' => [

        'cluster' => false,

        'default' => [
            'host'     => env('REDIS_LABS_HOST'),
            'port'     => env('REDIS_LABS_PORT'),
            'timeout'  => 1,
            'read_write_timeout' => 1,
            'options'  => [
                'parameters' => (empty(env('REDIS_LABS_PASSWORD')) === false) ? ['password' => env('REDIS_LABS_PASSWORD')] : [],
            ],
            'persistent' => true,
        ],

        'default_with_high_timeout' => [
            'host'     => env('REDIS_LABS_HOST'),
            'port'     => env('REDIS_LABS_PORT'),
            'timeout'  => 10,
            'read_write_timeout' => 10,
            'options'  => [
                'parameters' => (empty(env('REDIS_LABS_PASSWORD')) === false) ? ['password' => env('REDIS_LABS_PASSWORD')] : [],
            ],
            'persistent' => true,
        ],

        'secure' => [
            'host'     => env('SECURE_REDIS_HOST'),
            'port'     => env('SECURE_REDIS_PORT'),
            'database' => env('SECURE_REDIS_DB'),
            'timeout'  => 5,
            'read_write_timeout' => 1,
            'persistent' => true,
        ],
    ],

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
    'es_entity_type_prefix'   => env('ES_ENTITY_TYPE_PREFIX'),
);
