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
            'database' => env('DB_LIVE_DATABASE'),
            'driver'    => env('DB_LIVE_DRIVER'),
            'charset'   => 'utf8',
            'collation' => 'utf8_bin',
            'prefix'    => '',
            'strict'    => true,
            'lag_check' => [
                'driver' => 'redis',
                'flag'   => ConfigKey::MASTER_PERCENT,
                'percentage' =>  env('DB_MASTER_PERCENTAGE'),
                'read_from_config' =>  env('DB_MASTER_PERCENTAGE_READ_FROM_CONFIG'),
             ],
            'heartbeat_check' => [
                'driver'               => 'heartbeat',
                'force_run'            => env('HEARTBEAT_FORCE_RUN'),
                'enabled'              => env('HEARTBEAT_ENABLED', false),
                'mock'                 => env('HEARTBEAT_MOCK'),
                'time_threshold'       => env('HEARTBEAT_TIME_THRESHOLD'),
                'slave_time_threshold' => env('HEARTBEAT_SLAVE_TIME_THRESHOLD'),
                'traffic_percentage'   => env('HEARTBEAT_TRAFFIC_PERCENTAGE'),
                'log_verbose'          => env('HEARTBEAT_LOG_VERBOSE'),
            ],
            'wait_timeout'              => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout'  => env('DB_TRANSACTION_WAIT_TIMEOUT'),
            'options' => [
                PDO::ATTR_TIMEOUT => 2,
            ],
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
            'view_db'   => env('DB_LIVE_VIEW_DATABASE'),
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
            'database' => env('DB_TEST_DATABASE'),
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
                'driver'               => 'heartbeat',
                'force_run'            => env('HEARTBEAT_FORCE_RUN'),
                'enabled'              => env('HEARTBEAT_ENABLED', false),
                'mock'                 => env('HEARTBEAT_MOCK'),
                'time_threshold'       => env('HEARTBEAT_TIME_THRESHOLD'),
                'slave_time_threshold' => env('HEARTBEAT_SLAVE_TIME_THRESHOLD'),
                'traffic_percentage'   => env('HEARTBEAT_TRAFFIC_PERCENTAGE'),
                'log_verbose'          => env('HEARTBEAT_LOG_VERBOSE'),
            ],
            'wait_timeout'              => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout'  => env('DB_TRANSACTION_WAIT_TIMEOUT'),
        ],

        'data-warehouse-live' => [
            'read'  => [
                'host'      => env('DB_WAREHOUSE_HOST'),
                'port'      => env('DB_WAREHOUSE_PORT'),
                'database'  => env('DB_WAREHOUSE_DATABASE_LIVE'),
                'username'  => env('DB_WAREHOUSE_USERNAME'),
                'password'  => env('DB_WAREHOUSE_PASSWORD'),
            ],
            'write' => [
                'host'      => env('DB_LIVE_HOST'),
                'port'      => env('DB_LIVE_PORT'),
                'database'  => env('DB_LIVE_DATABASE'),
                'username'  => env('DB_LIVE_USERNAME'),
                'password'  => env('DB_LIVE_PASSWORD'),
            ],
            'driver'                   => env('DB_WAREHOUSE_DRIVER'),
            'lag_threshold'            => env('DB_WAREHOUSE_HEARTBEAT_LAG_THRESHOLD'),
            'charset'                  => 'utf8',
            'collation'                => 'utf8_bin',
            'prefix'                   => '',
            'strict'                   => true,
            'wait_timeout'             => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout' => env('DB_TRANSACTION_WAIT_TIMEOUT'),
            'tidb_opt_agg_push_down'   => env('DB_TIDB_OPT_AGG_PUSH_DOWN'),
            'tidb_replica_read'        => env('DB_TIDB_REPLICA_READ'),
            'options' => [
                PDO::ATTR_TIMEOUT => 2,
            ],
        ],

        'data-warehouse-test' => [
            'read'  => [
                'host'      => env('DB_WAREHOUSE_HOST'),
                'port'      => env('DB_WAREHOUSE_PORT'),
                'database'  => env('DB_WAREHOUSE_DATABASE_TEST'),
                'username'  => env('DB_WAREHOUSE_USERNAME'),
                'password'  => env('DB_WAREHOUSE_PASSWORD'),
            ],
            'write' => [
                'host'     => env('DB_TEST_HOST'),
                'port'     => env('DB_TEST_PORT'),
                'username' => env('DB_TEST_USERNAME'),
                'password' => env('DB_TEST_PASSWORD'),
                'database' => env('DB_TEST_DATABASE'),
            ],
            'driver'                   => env('DB_WAREHOUSE_DRIVER'),
            'lag_threshold'            => env('DB_WAREHOUSE_HEARTBEAT_LAG_THRESHOLD'),
            'charset'                  => 'utf8',
            'collation'                => 'utf8_bin',
            'prefix'                   => '',
            'strict'                   => true,
            'wait_timeout'             => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout' => env('DB_TRANSACTION_WAIT_TIMEOUT'),
            'tidb_opt_agg_push_down'   => env('DB_TIDB_OPT_AGG_PUSH_DOWN'),
            'tidb_replica_read'        => env('DB_TIDB_REPLICA_READ'),
            'options' => [
                PDO::ATTR_TIMEOUT => 2,
            ],
        ],

        'data-warehouse-merchant-live' => [
            'read'  => [
                'host'      => env('DB_WAREHOUSE_MERCHANT_HOST'),
                'port'      => env('DB_WAREHOUSE_MERCHANT_PORT'),
                'database'  => env('DB_WAREHOUSE_DATABASE_LIVE'),
                'username'  => env('DB_WAREHOUSE_USERNAME'),
                'password'  => env('DB_WAREHOUSE_PASSWORD'),
            ],
            'write' => [
                'host'      => env('DB_LIVE_HOST'),
                'port'      => env('DB_LIVE_PORT'),
                'database'  => env('DB_LIVE_DATABASE'),
                'username'  => env('DB_LIVE_USERNAME'),
                'password'  => env('DB_LIVE_PASSWORD'),
            ],
            'driver'                   => env('DB_WAREHOUSE_DRIVER'),
            'lag_threshold'            => env('DB_WAREHOUSE_HEARTBEAT_LAG_THRESHOLD'),
            'charset'                  => 'utf8',
            'collation'                => 'utf8_bin',
            'prefix'                   => '',
            'strict'                   => true,
            'wait_timeout'             => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout' => env('DB_TRANSACTION_WAIT_TIMEOUT'),
            'tidb_opt_agg_push_down'   => env('DB_TIDB_OPT_AGG_PUSH_DOWN'),
            'tidb_replica_read'        => env('DB_TIDB_REPLICA_READ'),
            'options' => [
                PDO::ATTR_TIMEOUT => 2,
            ],
        ],

        'data-warehouse-merchant-test' => [
            'read'  => [
                'host'      => env('DB_WAREHOUSE_MERCHANT_HOST'),
                'port'      => env('DB_WAREHOUSE_MERCHANT_PORT'),
                'database'  => env('DB_WAREHOUSE_DATABASE_TEST'),
                'username'  => env('DB_WAREHOUSE_USERNAME'),
                'password'  => env('DB_WAREHOUSE_PASSWORD'),
            ],
            'write' => [
                'host'     => env('DB_TEST_HOST'),
                'port'     => env('DB_TEST_PORT'),
                'username' => env('DB_TEST_USERNAME'),
                'password' => env('DB_TEST_PASSWORD'),
                'database' => env('DB_TEST_DATABASE'),
            ],
            'driver'                   => env('DB_WAREHOUSE_DRIVER'),
            'lag_threshold'            => env('DB_WAREHOUSE_HEARTBEAT_LAG_THRESHOLD'),
            'charset'                  => 'utf8',
            'collation'                => 'utf8_bin',
            'prefix'                   => '',
            'strict'                   => true,
            'wait_timeout'             => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout' => env('DB_TRANSACTION_WAIT_TIMEOUT'),
            'tidb_opt_agg_push_down'   => env('DB_TIDB_OPT_AGG_PUSH_DOWN'),
            'tidb_replica_read'        => env('DB_TIDB_REPLICA_READ'),
            'options' => [
                PDO::ATTR_TIMEOUT => 2,
            ],
        ],

        'data-warehouse-admin-live' => [
            'read'  => [
                'host'      => env('DB_WAREHOUSE_HOST'),
                'port'      => env('DB_WAREHOUSE_PORT'),
                'database'  => env('DB_WAREHOUSE_DATABASE_LIVE'),
                'username'  => env('DB_WAREHOUSE_USERNAME'),
                'password'  => env('DB_WAREHOUSE_PASSWORD'),
            ],
            'write' => [
                'host'      => env('DB_LIVE_HOST'),
                'port'      => env('DB_LIVE_PORT'),
                'database'  => env('DB_LIVE_DATABASE'),
                'username'  => env('DB_LIVE_USERNAME'),
                'password'  => env('DB_LIVE_PASSWORD'),
            ],
            'driver'                   => env('DB_WAREHOUSE_DRIVER'),
            'lag_threshold'            => env('DB_WAREHOUSE_HEARTBEAT_LAG_THRESHOLD'),
            'charset'                  => 'utf8',
            'collation'                => 'utf8_bin',
            'prefix'                   => '',
            'strict'                   => true,
            'wait_timeout'             => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout' => env('DB_TRANSACTION_WAIT_TIMEOUT'),
            'tidb_opt_agg_push_down'   => env('DB_TIDB_OPT_AGG_PUSH_DOWN'),
            'tidb_replica_read'        => env('DB_TIDB_REPLICA_READ'),
            'options' => [
                PDO::ATTR_TIMEOUT => 2,
            ],
        ],

        'data-warehouse-admin-test' => [
            'read'  => [
                'host'      => env('DB_WAREHOUSE_ADMIN_HOST'),
                'port'      => env('DB_WAREHOUSE_ADMIN_PORT'),
                'database'  => env('DB_WAREHOUSE_DATABASE_TEST'),
                'username'  => env('DB_WAREHOUSE_USERNAME'),
                'password'  => env('DB_WAREHOUSE_PASSWORD'),
            ],
            'write' => [
                'host'     => env('DB_TEST_HOST'),
                'port'     => env('DB_TEST_PORT'),
                'username' => env('DB_TEST_USERNAME'),
                'password' => env('DB_TEST_PASSWORD'),
                'database' => env('DB_TEST_DATABASE'),
            ],
            'driver'                   => env('DB_WAREHOUSE_DRIVER'),
            'lag_threshold'            => env('DB_WAREHOUSE_HEARTBEAT_LAG_THRESHOLD'),
            'charset'                  => 'utf8',
            'collation'                => 'utf8_bin',
            'prefix'                   => '',
            'strict'                   => true,
            'wait_timeout'             => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout' => env('DB_TRANSACTION_WAIT_TIMEOUT'),
            'tidb_opt_agg_push_down'   => env('DB_TIDB_OPT_AGG_PUSH_DOWN'),
            'tidb_replica_read'        => env('DB_TIDB_REPLICA_READ'),
            'options' => [
                PDO::ATTR_TIMEOUT => 2,
            ],
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
            'view_db'   => env('DB_TEST_VIEW_DATABASE'),
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

        'payment-fetch-replica-live' => [
            'driver'    => env('PAYMENT_FETCH_DB_LIVE_DRIVER', env('REPORTING_DB_LIVE_DRIVER')),
            'host'      => env('PAYMENT_FETCH_DB_LIVE_HOST', env('REPORTING_DB_LIVE_HOST')),
            'port'      => env('PAYMENT_FETCH_DB_LIVE_PORT', env('REPORTING_DB_LIVE_PORT')),
            'database'  => env('PAYMENT_FETCH_DB_LIVE_DATABASE', env('REPORTING_DB_LIVE_DATABASE')),
            'username'  => env('PAYMENT_FETCH_REPLICA_USERNAME', env('SLAVE_DB_LIVE_USERNAME')),
            'password'  => env('PAYMENT_FETCH_REPLICA_PASSWORD', env('SLAVE_DB_LIVE_PASSWORD')),
            'charset'   => 'utf8',
            'collation' => 'utf8_bin',
            'prefix'    => '',
            'strict'    => true,
            'wait_timeout'              => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout'  => env('DB_TRANSACTION_WAIT_TIMEOUT'),
        ],

        'payment-fetch-replica-test' => [
            'driver'    => env('PAYMENT_FETCH_DB_TEST_DRIVER', env('REPORTING_DB_TEST_DRIVER')),
            'host'      => env('PAYMENT_FETCH_DB_TEST_HOST', env('REPORTING_DB_TEST_HOST')),
            'port'      => env('PAYMENT_FETCH_DB_TEST_PORT', env('REPORTING_DB_TEST_PORT')),
            'database'  => env('PAYMENT_FETCH_DB_TEST_DATABASE', env('REPORTING_DB_TEST_DATABASE')),
            'username'  => env('PAYMENT_FETCH_REPLICA_TEST_USERNAME', env('SLAVE_DB_TEST_USERNAME')),
            'password'  => env('PAYMENT_FETCH_REPLICA_TEST_PASSWORD', env('SLAVE_DB_TEST_PASSWORD')),
            'charset'   => 'utf8',
            'collation' => 'utf8_bin',
            'prefix'    => '',
            'strict'    => true,
            'wait_timeout'              => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout'  => env('DB_TRANSACTION_WAIT_TIMEOUT'),
        ],

        'account-service-replica-live' => [
            'driver'    => env('PAYMENT_FETCH_DB_LIVE_DRIVER'),
            'host'      => env('PAYMENT_FETCH_DB_LIVE_HOST', env('REPORTING_DB_LIVE_HOST')),
            'port'      => env('PAYMENT_FETCH_DB_LIVE_PORT', env('REPORTING_DB_LIVE_PORT')),
            'database'  => env('PAYMENT_FETCH_DB_LIVE_DATABASE', env('REPORTING_DB_LIVE_DATABASE')),
            'username'  => env('ASV_SLAVE_DATABASE_USER'),
            'password'  => env('ASV_SLAVE_DATABASE_PASSWORD'),
            'charset'   => 'utf8',
            'collation' => 'utf8_bin',
            'prefix'    => '',
            'strict'    => true,
            'wait_timeout'              => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout'  => env('DB_TRANSACTION_WAIT_TIMEOUT'),
        ],

        'archived-data-replica-live' => [
            'host'                     => env('ARCHIVED_REPLICA_DB_LIVE_HOST'),
            'port'                     => env('ARCHIVED_REPLICA_DB_LIVE_PORT'),
            'username'                 => env('ARCHIVED_REPLICA_DB_LIVE_USERNAME'),
            'password'                 => env('ARCHIVED_REPLICA_DB_LIVE_PASSWORD'),
            'database'                 => env('DB_LIVE_DATABASE'),
            'driver'                   => env('DB_LIVE_DRIVER'),
            'charset'                  => 'utf8',
            'collation'                => 'utf8_bin',
            'prefix'                   => '',
            'strict'                   => true,
            'wait_timeout'             => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout' => env('DB_TRANSACTION_WAIT_TIMEOUT'),
        ],

        'archived-data-replica-test' => [
            'host'                     => env('ARCHIVED_REPLICA_DB_TEST_HOST'),
            'port'                     => env('ARCHIVED_REPLICA_DB_TEST_PORT'),
            'username'                 => env('ARCHIVED_REPLICA_DB_TEST_USERNAME'),
            'password'                 => env('ARCHIVED_REPLICA_DB_TEST_PASSWORD'),
            'database'                 => env('DB_TEST_DATABASE'),
            'driver'                   => env('DB_TEST_DRIVER'),
            'charset'                  => 'utf8',
            'collation'                => 'utf8_bin',
            'prefix'                   => '',
            'strict'                   => true,
            'wait_timeout'             => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout' => env('DB_TRANSACTION_WAIT_TIMEOUT'),
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
            'host'     => env('DB_UPI_PAYMENTS_LIVE_HOST'),
            'port'     => env('DB_UPI_PAYMENTS_LIVE_PORT'),
            'username' => env('DB_UPI_PAYMENTS_LIVE_USERNAME'),
            'password' => env('DB_UPI_PAYMENTS_LIVE_PASSWORD'),
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
            'host'     => env('DB_UPI_PAYMENTS_TEST_HOST'),
            'port'     => env('DB_UPI_PAYMENTS_TEST_PORT'),
            'username' => env('DB_UPI_PAYMENTS_TEST_USERNAME'),
            'password' => env('DB_UPI_PAYMENTS_TEST_PASSWORD'),
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

        'table_partition_live' => [
            'host'      => env('DB_LIVE_HOST'),
            'port'      => env('DB_LIVE_PORT'),
            'username'  => env('DB_LIVE_PARTITION_MGR_USERNAME'),
            'password'  => env('DB_LIVE_PARTITION_MGR_PASSWORD'),
            'database'  => env('DB_LIVE_DATABASE'),
            'driver'    => env('DB_LIVE_DRIVER'),
            'charset'   => 'utf8',
            'collation' => 'utf8_bin',
            'prefix'    => '',
            'strict'    => true,
            'wait_timeout'              => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout'  => env('DB_TRANSACTION_WAIT_TIMEOUT'),
        ],

        'table_partition_test' => [
            'host'      => env('DB_TEST_HOST'),
            'port'      => env('DB_TEST_PORT'),
            'username'  => env('DB_TEST_PARTITION_MGR_USERNAME'),
            'password'  => env('DB_TEST_PARTITION_MGR_PASSWORD'),
            'database'  => env('DB_TEST_DATABASE'),
            'driver'    => env('DB_TEST_DRIVER'),
            'charset'   => 'utf8',
            'collation' => 'utf8_bin',
            'prefix'    => '',
            'strict'    => true,
            'wait_timeout'              => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout'  => env('DB_TRANSACTION_WAIT_TIMEOUT'),
        ],

        'rx_account_statements_live' => [
            'read'  => [
                'host'     => env('RX_ACCOUNT_STATEMENTS_LIVE_HOST'),
                'port'     => env('RX_ACCOUNT_STATEMENTS_LIVE_PORT'),
                'username' => env('RX_ACCOUNT_STATEMENTS_LIVE_USERNAME'),
                'password' => env('RX_ACCOUNT_STATEMENTS_LIVE_PASSWORD'),
            ],
            'write' => [
                'host'     => env('RX_ACCOUNT_STATEMENTS_LIVE_HOST'),
                'port'     => env('RX_ACCOUNT_STATEMENTS_LIVE_PORT'),
                'username' => env('RX_ACCOUNT_STATEMENTS_LIVE_USERNAME'),
                'password' => env('RX_ACCOUNT_STATEMENTS_LIVE_PASSWORD'),
            ],
            'sticky'    => true,
            'database' => env('RX_ACCOUNT_STATEMENTS_LIVE_DATABASE'),
            'driver'    => env('RX_ACCOUNT_STATEMENTS_LIVE_DRIVER'),
            'charset'   => 'utf8',
            'collation' => 'utf8_bin',
            'prefix'    => '',
            'strict'    => true,
            'wait_timeout'              => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout'  => env('DB_TRANSACTION_WAIT_TIMEOUT'),
        ],

        'payout_service_database' => [
            'read'  => [
                'host'     => env('PAYOUT_SERVICE_LIVE_HOST'),
                'port'     => env('PAYOUT_SERVICE_LIVE_PORT'),
                'username' => env('PAYOUT_SERVICE_LIVE_USERNAME'),
                'password' => env('PAYOUT_SERVICE_LIVE_PASSWORD'),
            ],
            'write' => [
                'host'     => env('PAYOUT_SERVICE_LIVE_HOST'),
                'port'     => env('PAYOUT_SERVICE_LIVE_PORT'),
                'username' => env('PAYOUT_SERVICE_LIVE_USERNAME'),
                'password' => env('PAYOUT_SERVICE_LIVE_PASSWORD'),
            ],
            'sticky'                   => true,
            'database'                 => env('PAYOUT_SERVICE_LIVE_DATABASE'),
            'driver'                   => env('PAYOUT_SERVICE_LIVE_DRIVER'),
            'charset'                  => 'utf8',
            'collation'                => 'utf8_bin',
            'prefix'                   => '',
            'strict'                   => true,
            'wait_timeout'             => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout' => env('DB_TRANSACTION_WAIT_TIMEOUT'),
        ],

        'rx_whatsapp_live' => [
            'write'  => [
                'host'     => env('RX_WHATSAPP_LIVE_HOST'),
                'port'     => env('RX_WHATSAPP_LIVE_PORT'),
                'username' => env('RX_WHATSAPP_LIVE_USERNAME'),
                'password' => env('RX_WHATSAPP_LIVE_PASSWORD'),
            ],
            'read' => [
                'host'     => env('RX_WHATSAPP_SLAVE_LIVE_HOST'),
                'port'     => env('RX_WHATSAPP_SLAVE_LIVE_PORT'),
                'username' => env('RX_WHATSAPP_SLAVE_LIVE_USERNAME'),
                'password' => env('RX_WHATSAPP_SLAVE_LIVE_PASSWORD'),
            ],
            'sticky'    => true,
            'database' => env('RX_WHATSAPP_LIVE_DATABASE'),
            'driver'    => 'mysql',
            'charset'   => 'utf8',
            'collation' => 'utf8_bin',
            'prefix'    => '',
            'strict'    => true,
            'wait_timeout'              => env('DB_WAIT_TIMEOUT'),
            'transaction_wait_timeout'  => env('DB_TRANSACTION_WAIT_TIMEOUT'),
        ],

        'rx_whatsapp_slave_live' => [
                // Slave must have the same driver and DB names as the master.
                'driver'    => 'mysql',
                'database'  => env('RX_WHATSAPP_LIVE_DATABASE'),
                'host'      => env('RX_WHATSAPP_SLAVE_LIVE_HOST'),
                'port'      => env('RX_WHATSAPP_SLAVE_LIVE_PORT'),
                'username'  => env('RX_WHATSAPP_SLAVE_LIVE_USERNAME'),
                'password'  => env('RX_WHATSAPP_SLAVE_LIVE_PASSWORD'),
                'charset'   => 'utf8',
                'collation' => 'utf8_bin',
                'prefix'    => '',
                'strict'    => true,
                'wait_timeout'              => env('DB_WAIT_TIMEOUT'),
                'transaction_wait_timeout'  => env('DB_TRANSACTION_WAIT_TIMEOUT'),
            ],

        'proxy_sql_unix_socket' => env('PROXY_SQL_UNIX_SOCKET'),

        'proxy_sql_enable_payment_fetch_replica' => env('PROXY_SQL_ENABLE_PAYMENT_FETCH_REPLICA', false),

        'proxy_sql_service_config' => [
            'host'  => env('PROXY_SQL_SERVICE_HOST'),
            'port'  => env('PROXY_SQL_SERVICE_PORT')
        ],
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
    | DB Query log sampling rate -- count per 1000 queries
    |--------------------------------------------------------------------------
    |
    | This is the query sampling rate for all db queries. Value is count
    | of queries to be logged out of 1000 queries.
    */
    'db_mysql_query_sampling_rate' => env('DB_MYSQL_QUERY_SAMPLING_RATE', 1),

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

        'client' => 'predis',

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
        ],

        // NOTE: DO NOT use this connection in any environment!!
        //
        // We use 1 redis node in clustered mode for unit tests, for which
        // iterating over nodes to flush doesn't work. So, instead we connect
        // to redis in single node mode and run FLUSHDB.
        // See \RZP\Tests\Functional\TestCase::flushCache
        'unit_tests_connection' => [
            'host'     => env('MUTEX_REDIS_HOST'),
            'port'     => env('MUTEX_REDIS_PORT'),
            'timeout'  => 1,
            'read_write_timeout' => 1,
            'persistent' => true,
        ],

        // This connection is used by \RZP\Services\CredcaseSigner.
        'credcase_signer' => [
            'scheme'             => env('CREDCASE_SIGNER_REDIS_SCHEME'),
            'host'               => env('CREDCASE_SIGNER_REDIS_HOST'),
            'port'               => env('CREDCASE_SIGNER_REDIS_PORT'),
            'username'           => env('CREDCASE_SIGNER_REDIS_USERNAME'),
            'password'           => env('CREDCASE_SIGNER_REDIS_PASSWORD'),
            'timeout'            => env('CREDCASE_SIGNER_REDIS_TIMEOUT'),
            'read_write_timeout' => env('CREDCASE_SIGNER_REDIS_READ_WRITE_TIMEOUT'),
            'persistent'         => true,
            // TODO: To figure out and fix certificate verification.
            'ssl'                => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ],

        'secure' => [
            'host'     => env('SECURE_REDIS_HOST'),
            'port'     => env('SECURE_REDIS_PORT'),
            'database' => env('SECURE_REDIS_DB'),
            'timeout'  => 5,
            'read_write_timeout' => 1,
            'persistent' => true,
        ],

        'clusters' => [
            'default' => [
                [
                    'host'     => env('MUTEX_REDIS_HOST'),
                    'port'     => env('MUTEX_REDIS_PORT'),
                    'timeout'  => 1,
                    'read_write_timeout' => 1,
                    'persistent' => true,
                ],
            ],

            'query_cache_redis' => [
                [
                    'host'     => env('QUERY_CACHE_REDIS_HOST'),
                    'port'     => env('QUERY_CACHE_REDIS_PORT'),
                    'timeout'  => 1,
                    'read_write_timeout' => 1,
                    'persistent' => true,
                ]
            ],

            'mutex_redis' => [
                [
                    'host'     => env('MUTEX_REDIS_HOST'),
                    'port'     => env('MUTEX_REDIS_PORT'),
                    'timeout'  => 1,
                    'read_write_timeout' => 1,
                    'persistent' => true,
                ]
            ],

            'session_redis' => [
                [
                    'host'     => env('SESSION_REDIS_HOST'),
                    'port'     => env('SESSION_REDIS_PORT'),
                    'timeout'  => 1,
                    'read_write_timeout' => 1,
                    'persistent' => true,
                ]
            ],

            'session_redis_v2' => [
                [
                    'host'     => env('SESSION_REDIS_HOST_V2'),
                    'port'     => env('SESSION_REDIS_PORT_V2'),
                    'timeout'  => 1,
                    'read_write_timeout' => 1,
                    'persistent' => true,
                ]
            ],

            'throttle' => [
                [
                    'host'               => env('THROTTLE_REDIS_HOST'),
                    'port'               => env('THROTTLE_REDIS_PORT'),
                    'timeout'            => 1,
                    'read_write_timeout' => 1,
                    'persistent'         => true,
                ]
            ],
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

    'dedupe_es_host'          => env('DEDUPE_ES_HOST'),

    'dedupe_es_mock'          => env('DEDUPE_ES_MOCK'),

    'es_workflow_action_mock' => env('ES_WORKFLOW_ACTION_MOCK', false),

    'es_entity_index_prefix'  => env('ES_ENTITY_INDEX_PREFIX'),
    'es_entity_type_prefix'   => env('ES_ENTITY_TYPE_PREFIX'),
);
