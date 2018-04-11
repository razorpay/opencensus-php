<?php

namespace RZP\Base\Database;

use Closure;
use RZP\Foundation\Application;

class RedisLagChecker implements LagChecker
{
    protected $app;

    protected $config;

    protected $trace;

    protected $cache;

    public function __construct(Application $app, array $config)
    {
        $this->app = $app;
        $this->trace = $app['trace'];
        $this->cache = $app['cache'];
        $this->config = $config;
    }

    public function useReadPdoIfApplciable(Closure $readPdo)
    {
        $skipSlave = false;

        try
        {
            $skipSlave = boolval($this->cache->get($this->config['flag']));
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex);
        }

        return ($skipSlave === false) ? call_user_func($readPdo) : null;
    }
}
