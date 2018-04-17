<?php

namespace RZP\Base\Database\LagChecker;

use Closure;

/**
 * Checks replication lag by querying heartbeat table on the
 * replica connection.
 *
 * @todo Needs to be implemented
 */
class HeartbeatLagChecker implements LagChecker
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function useReadPdoIfApplicable(Closure $readPdo)
    {
        return null;
    }
}
