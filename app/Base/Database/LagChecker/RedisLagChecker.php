<?php

namespace RZP\Base\Database\LagChecker;

use Cache;
use Closure;
use Razorpay\Trace\Facades\Trace;
use Razorpay\Trace\Logger as LogLevel;

use RZP\Trace\TraceCode;

/**
 * Checks whether to use replica connection based on redis check
 */
class RedisLagChecker implements LagChecker
{
    protected $config;

    protected $trace;

    protected $cache;

    public function __construct(array $config)
    {
        $this->trace = Trace::getFacadeRoot();
        $this->cache = Cache::getFacadeRoot();
        $this->config = $config;
    }

    /**
     * Queries the 'skip_slave' flag on redis. Establishes
     * the read connection only if the value is false.
     *
     * @param  Closure $readPdo
     */
    public function useReadPdoIfApplciable(Closure $readPdo)
    {
        $skipSlave = false;

        try
        {
            $skipSlave = boolval($this->cache->get($this->config['flag']));
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                LogLevel::CRITICAL,
                TraceCode::REDIS_LAG_CHECK_FAILED);
        }

        return ($skipSlave === false) ? call_user_func($readPdo) : null;
    }
}
