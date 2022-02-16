<?php

namespace Database;

class Connection
{
    const TEST                  = 'test';
    const LIVE                  = 'live';
    const SLAVE_TEST            = 'slave-test';
    const SLAVE_LIVE            = 'slave-live';
    const MASTER_REPLICA_TEST   = 'master-replica-test';
    const MASTER_REPLICA_LIVE   = 'master-replica-live';
    const DATA_WAREHOUSE_LIVE   = 'data-warehouse-live'; // goes to admin cluster
    const DATA_WAREHOUSE_TEST   = 'data-warehouse-test';
    const REPORTING_REPLICA_LIVE = 'reporting-replica-live';
    const REPORTING_REPLICA_TEST = 'reporting-replica-test';

    const PAYMENT_FETCH_REPLICA_LIVE = 'payment-fetch-replica-live';

    const PAYMENT_ANALYTICS_PARTITION_LIVE = 'payment_analytics_partition_live';
    const PAYMENT_ANALYTICS_PARTITION_TEST = 'payment_analytics_partition_test';

    // different tidb cluster for merchant queries and for admin queries
    const DATA_WAREHOUSE_ADMIN_LIVE = 'data-warehouse-admin-live'; // same as data warehouse live
    const DATA_WAREHOUSE_ADMIN_TEST = 'data-warehouse-admin-test';
    const DATA_WAREHOUSE_MERCHANT_TEST = 'data-warehouse-merchant-test';
    const DATA_WAREHOUSE_MERCHANT_LIVE = 'data-warehouse-merchant-live';

    const RX_ACCOUNT_STATEMENTS_LIVE = 'rx_account_statements_live';

    const DATA_WAREHOUSE_CONNECTIONS = [
        self::DATA_WAREHOUSE_LIVE,
        self::DATA_WAREHOUSE_TEST,
        self::DATA_WAREHOUSE_ADMIN_LIVE,
        self::DATA_WAREHOUSE_MERCHANT_LIVE,
        self::DATA_WAREHOUSE_ADMIN_TEST,
        self::DATA_WAREHOUSE_MERCHANT_TEST,
    ];
}
