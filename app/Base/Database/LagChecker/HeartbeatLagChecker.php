<?php

namespace RZP\Base\Database\LagChecker;

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

    /**
     * {@inheritDoc}
     */
    public function useReadPdoIfApplicable($readPdo)
    {
        return null;
    }
}
