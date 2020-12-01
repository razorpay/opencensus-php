<?php

namespace RZP\Base\Database;

final class Metric
{
    // ------------------------- Metrics ------------------------- //
    /**
     * Method: Count
     * Dimensions: Master
     */
    const ENFORCE_MASTER_CONNECTION     = 'enforce_master_connection';
    const HEARTBEAT_REPLICA_LAG         = 'heartbeat_replica_lag';
    const DATAWAREHOUSE_REPLICATION_LAG = 'datawarehouse_replication_lag';

    // ------------------------- dimensions ------------------------- //

    const CONNECTION         = 'connection';
    const LAG                = 'lag';
    const DATABASE_RECONNECT = 'database_reconnect';

    // ------------------------- values ------------------------- //
    const MASTER                           = 'master';

    const SLAVE                            = 'slave';
}
