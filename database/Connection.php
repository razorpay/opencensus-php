<?php

namespace Database;

class Connection
{
    const TEST                  = 'test';
    const LIVE                  = 'live';
    const SLAVE_TEST            = 'slave-test';
    const SLAVE_LIVE            = 'slave-live';
    const DATA_WAREHOUSE_LIVE   = 'data-warehouse-live'; // goes to admin cluster
    const DATA_WAREHOUSE_TEST   = 'data-warehouse-test';

    const ARCHIVED_DATA_REPLICA_LIVE = 'archived-data-replica-live';
    const ARCHIVED_DATA_REPLICA_TEST = 'archived-data-replica-test';

    const PAYMENT_FETCH_REPLICA_LIVE = 'payment-fetch-replica-live';
    const PAYMENT_FETCH_REPLICA_TEST = 'payment-fetch-replica-test';
    const ACCOUNT_SERVICE_REPLICA_LIVE = 'account-service-replica-live';

    const TABLE_PARTITION_LIVE = 'table_partition_live';
    const TABLE_PARTITION_TEST = 'table_partition_test';

    // different tidb cluster for merchant queries and for admin queries
    const DATA_WAREHOUSE_ADMIN_LIVE = 'data-warehouse-admin-live'; // same as data warehouse live
    const DATA_WAREHOUSE_ADMIN_TEST = 'data-warehouse-admin-test';
    const DATA_WAREHOUSE_MERCHANT_TEST = 'data-warehouse-merchant-test';
    const DATA_WAREHOUSE_MERCHANT_LIVE = 'data-warehouse-merchant-live';

    const RX_ACCOUNT_STATEMENTS_LIVE = 'rx_account_statements_live';

    const PAYOUT_SERVICE_DATABASE = 'payout_service_database';

    const RX_WHATSAPP_LIVE = 'rx_whatsapp_live';

    const RX_WHATSAPP_SLAVE_LIVE = 'rx_whatsapp_slave_live';

    // connections to apply _record_source = 'api' filter on warm storage queries
    const DATA_WAREHOUSE_MERCHANT_SOURCE_API_TEST = 'data-warehouse-merchant-api-test';
    const DATA_WAREHOUSE_MERCHANT_SOURCE_API_LIVE = 'data-warehouse-merchant-api-live';
    const DATA_WAREHOUSE_ADMIN_SOURCE_API_LIVE    = 'data-warehouse-admin-api-live';
    const DATA_WAREHOUSE_ADMIN_SOURCE_API_TEST    = 'data-warehouse-admin-api-test';

    const DATA_WAREHOUSE_CONNECTIONS = [
        self::DATA_WAREHOUSE_LIVE,
        self::DATA_WAREHOUSE_TEST,
        self::DATA_WAREHOUSE_ADMIN_LIVE,
        self::DATA_WAREHOUSE_MERCHANT_LIVE,
        self::DATA_WAREHOUSE_ADMIN_TEST,
        self::DATA_WAREHOUSE_MERCHANT_TEST,
        self::DATA_WAREHOUSE_ADMIN_SOURCE_API_LIVE,
        self::DATA_WAREHOUSE_ADMIN_SOURCE_API_TEST,
        self::DATA_WAREHOUSE_MERCHANT_SOURCE_API_LIVE,
        self::DATA_WAREHOUSE_MERCHANT_SOURCE_API_TEST,
    ];

    // Has mapping to corresponding warm database connections
    const DATA_WAREHOUSE_SOURCE_API_CONNECTIONS = [
        self::DATA_WAREHOUSE_ADMIN_SOURCE_API_LIVE    => self::DATA_WAREHOUSE_ADMIN_LIVE,
        self::DATA_WAREHOUSE_ADMIN_SOURCE_API_TEST    => self::DATA_WAREHOUSE_ADMIN_TEST ,
        self::DATA_WAREHOUSE_MERCHANT_SOURCE_API_LIVE => self::DATA_WAREHOUSE_MERCHANT_LIVE,
        self::DATA_WAREHOUSE_MERCHANT_SOURCE_API_TEST => self::DATA_WAREHOUSE_MERCHANT_TEST,
    ];
}
