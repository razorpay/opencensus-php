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
    // connection identifiers

    const MASTER      = 'master';

    const SLAVE       = 'slave';

    /**
     * @var array
     */
    protected $config;

    /**
     * @var Trace
     */
    protected $trace;

    /**
     * @var int
     */
    protected $weight;

    public function __construct(array $config)
    {
        $this->trace  = TraceFacade::getFacadeRoot();

        $this->config = $config;

        //
        // set this once for connection because this will execute multiple times for single request
        //
        $this->weight = rand(1, 100);
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
            $skipSlavePercentage = (int) Cache::get($this->config['flag']);

            //
            // If skip_slave config is set to 0 or any non integer character, it will always go to master
            //
            // If skip_slave config is set to number between 1 - 100 (inclusive of both),
            // we can skip slave (route to master) for percentage mentioned.
            // For example: if skip_slave = 10,
            // 10% of the traffic will skip slave (request is served by master)
            // remaining 90% will NOT skip slave (request is served by slave)
            //

            if ($skipSlavePercentage === 0)
            {
                $skipSlave = false;
            }
            else
            {
                $skipSlave = ($this->weight <= $skipSlavePercentage);

                $connection = ($skipSlave === true) ? self::MASTER : self::SLAVE;

                $this->trace->info(
                    TraceCode::WEIGHTED_DATABASE_ROUTING,
                    [
                        'connection' => $connection,
                    ]);
            }
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
