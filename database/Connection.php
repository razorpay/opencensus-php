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
    const DATA_WAREHOUSE_LIVE   = 'data-warehouse-live';
    const DATA_WAREHOUSE_TEST   = 'data-warehouse-test';
    const REPORTING_REPLICA_LIVE = 'reporting-replica-live';
    const REPORTING_REPLICA_TEST = 'reporting-replica-test';

    const PAYMENT_ANALYTICS_PARTITION_LIVE = 'payment_analytics_partition_live';
    const PAYMENT_ANALYTICS_PARTITION_TEST = 'payment_analytics_partition_test';
}
