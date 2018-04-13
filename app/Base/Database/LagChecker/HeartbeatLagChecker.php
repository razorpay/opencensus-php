<?php

namespace RZP\Base\Database\LagChecker;

use Closure;
use RZP\Foundation\Application;

/**
 * Checks replication lag by querying heartbeat table on the
 * replica connection. Needs to be implemented
 */
class HeartbeatLagChecker implements LagChecker
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function useReadPdoIfApplciable(Closure $readPdo)
    {
        return null;
    }
}
