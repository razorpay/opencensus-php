<?php

namespace RZP\Base\Database;

use Closure;
use RZP\Foundation\Application;

class HeartbeatLagChecker implements LagChecker
{
    public function __construct(Application $app, array $config)
    {
        $this->app = $app;
        $this->trace = $app['trace'];
        $this->cache = $app['cache'];
        $this->config = $config;
    }

    public function useReadPdoIfApplciable(Closure $readPdo)
    {
        return null;
    }
}
