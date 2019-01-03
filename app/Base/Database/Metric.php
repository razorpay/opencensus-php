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

    // ------------------------- dimensions ------------------------- //

    const CONNECTION = 'connection';

    // ------------------------- values ------------------------- //
    const MASTER                           = 'master';

    const SLAVE                            = 'slave';
}
