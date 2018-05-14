<?php

namespace RZP\Base\Database\LagChecker;

use Cache;
use Closure;
use Razorpay\Trace\Logger as Trace;
use Razorpay\Trace\Facades\Trace as TraceFacade;

use RZP\Trace\TraceCode;

/**
 * Checks whether to use replica connection based on redis check
 */
class RedisLagChecker implements LagChecker
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var Trace
     */
    protected $trace;


    public function __construct(array $config)
    {
        $this->trace  = TraceFacade::getFacadeRoot();
        $this->config = $config;
    }

    /**
     * Queries the 'skip_slave' flag on redis. Establishes
     * the read connection only if the value is false.
     *
     * @param  \PDO|Closure $readPdo
     * @return \PDO|null
     */
    public function useReadPdoIfApplicable($readPdo)
    {
        $skipSlave = true;

        try
        {
            $skipSlave = (bool) Cache::get($this->config['flag']);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::REDIS_LAG_CHECK_FAILED);
        }

        // If should skip slave, return null so master connection is used, else resolve $readPdo and return
        return $skipSlave === true ? null : ($readPdo instanceof Closure ? call_user_func($readPdo) : $readPdo);
    }
}
