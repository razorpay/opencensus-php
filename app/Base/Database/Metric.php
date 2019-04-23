<?php

namespace RZP\Base\Database;

final class Metric
{
    // ------------------------- Metrics ------------------------- //
    /**
     * Method: Count
     * Dimensions: Master
     */
    const ENFORCE_MASTER_CONNECTION = 'enforce_master_connection';
    const HEARTBEAT_REPLICA_LAG     = 'heartbeat_replica_lag';

    // ------------------------- dimensions ------------------------- //

    const CONNECTION = 'connection';
    const LAG        = 'lag';

    // ------------------------- values ------------------------- //
    const MASTER                           = 'master';

    const SLAVE                            = 'slave';
}
