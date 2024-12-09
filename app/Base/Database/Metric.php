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
    const DATABASE_ERROR_CLASSIFICATION = 'database_error_classification';

    const DATABASE_QUERY_BINDING        = 'database_query_binding';

    const TABLE_OPERATION_QUERY_BINDING        = 'table_operation_query_binding';

    const ASV_DATABASE_QUERY_BINDING        = 'asv_database_query_binding';

    // ------------------------- dimensions ------------------------- //

    const CONNECTION         = 'connection';
    const LAG                = 'lag';
    const DATABASE_RECONNECT = 'database_reconnect';

    // ------------------------- values ------------------------- //
    const MASTER                           = 'master';

    const SLAVE                            = 'slave';
}
