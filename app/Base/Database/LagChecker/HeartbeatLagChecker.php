<?php

namespace RZP\Base\Database\LagChecker;

use App;
use Closure;
use Carbon\Carbon;
use Illuminate\Redis\RedisManager;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Http\RequestContext;
use RZP\Base\Database\Metric;

/**
 * Checks replication lag by querying heartbeat table on the
 * replica connection.
 *
 */
class HeartbeatLagChecker implements LagChecker
{
    // slave connection identifier
    const SLAVE     = 'slave';

    // master connection identifier
    const MASTER    = 'master';

    // heartbeat connection identifier
    const HEARTBEAT = 'heartbeat';

    /**
     * @var RequestContext
     */
    protected $reqCtx;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var int
     */
    protected $randomTrafficPercent;

    /**
     * @var RedisManager
     */
    protected $redis;

    /**
     * @var CacheManager
     */
    protected $cache;

    /**
     * @var Trace
     */
    protected $trace;


    /**
     * @var int
     */
    protected $lag;

    /**
     * holds the handler for connection identifier
     *
     * @var array
     */
    protected $connectionResolver;

    /**
     * flag which indicates if the heartbeat is enabled
     *
     * @var bool
     */
    private $enabled;

    /**
     * if true heartbeat result will only get logged and result wont affect the connection
     *
     * @var bool
     */
    private $mock;

    /**
     * heartbeat threshold value in mili sec
     *
     * @var int
     */
    private $timeThreshold;

    /**
     * heartbeat threshold value in mili sec for slave connections
     *
     * @var int
     */
    private $slaveTimeThreshold;

    /**
     * heartbeat ramp percentage
     *
     * @var int
     */
    private $trafficPercent;

    public function __construct(array $config)
    {
        $this->config = $config;

        //
        // traffic percent weight will be generated only once for a connection.
        // We do multiple get connection on a request.
        // having this generated every time will might switch the connection between read and write replica.
        //
        $this->randomTrafficPercent = rand(1, 100);

        $app = App::getFacadeRoot();

        $this->trace  = $app['trace'];

        $this->reqCtx = $app['request.ctx'];

        $this->redis = $app['redis']->connection('redis_labs');

        $this->cache = $app['cache'];

        $this->initializeConnectionResolvers();
    }

    /**
     * {@inheritDoc}
     */
    public function useReadPdoIfApplicable($readPdo)
    {
        $useSlave = false;

        try
        {
            //
            // Load configs from cache only of heartbeat evaluation is required
            // this is because we might not even check heartbeat in case of master_percentage check passes
            //
            $this->loadConfigs();

            // perform heartbeat check
            $useSlave = $this->shouldUseSlave($readPdo);

            $this->traceConnectionSelection(TraceCode::HEARTBEAT_DATABASE_ROUTING, $useSlave);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::HEARTBEAT_CHECK_FAILED);
        }

        // If should skip slave, return null so master connection is used, else resolve $readPdo and return
        return $useSlave === true ?
            ($readPdo instanceof Closure ? call_user_func($readPdo) : $readPdo) :
            null;
    }

    /**
     * It'll do series of operations:
     * 1. Check if heartbeat is enabled. if not use master
     * 2. If the route identifier is invalid then use master
     * 3. call resolved based on connection identifier
     * 4. check the ramp %. if not satisfied then use master
     *
     * @param $readPdo
     * @return bool
     */
    protected function shouldUseSlave($readPdo): bool
    {
        $useSlave = false;

        //
        // If pt-heartbeat is disabled then use slave connection as master % route suggests
        //
        if ($this->enabled === false)
        {
            $useSlave = true;

            return $this->finalizeResult($useSlave);
        }

        $currentRoute = $this->reqCtx->getRoute();

        $connectionIdentifier = $this->redis->hget($this->config['routes'], $currentRoute);

        //
        // if connection identifier set is invalid then use master
        //
        if (isset($this->connectionResolver[$connectionIdentifier]) === false)
        {
            return $this->finalizeResult($useSlave);
        }

        //
        // call connection resolved for given connection identifier
        //
        $useSlave = $this->connectionResolver[$connectionIdentifier]($readPdo);

        $this->traceConnectionSelection(
            TraceCode::HEARTBEAT_CHECK_COMPLETED,
            $useSlave,
            [
                'route_name'            => $currentRoute,
                'connection_identifier' => $connectionIdentifier,
            ]);

        //
        // if the random weight is greater than threshold then move traffic to master
        //
        if ($this->randomTrafficPercent > $this->trafficPercent)
        {
            $useSlave = false;

            return $this->finalizeResult($useSlave);
        }

        return $this->finalizeResult($useSlave);
    }

    /**
     * checks id the difference between system time and last updated heartbeat time is greater than threshold given
     *
     * @param $readPdo
     * @param $threshold
     *
     * @return bool
     */
    protected function isSlaveLagging($readPdo, $threshold): bool
    {
        $pdo = ($readPdo instanceof Closure) ? call_user_func($readPdo) : $readPdo;

        //
        // Using raw query here as we can not use model or eloquent builder here
        // as it also calls this flow to get the connection
        //
        $query = 'SELECT ROUND(( ROUND(UNIX_TIMESTAMP(Now(6)) * 1000000) - ( 
                            UNIX_TIMESTAMP(SUBSTR(ts, 1, 19)) * 1000000 + 
                            SUBSTR(ts, 21, 6) ) 
                         ) / 1000) AS replica_lag_milli, ts 
                    FROM   heartbeat.heartbeat
                    LIMIT  1';

        $result = $pdo->query($query)->fetch();

        $this->lag = $result['replica_lag_milli'];

        return ($this->lag > $threshold);
    }

    /**
     * Get the difference in microseconds for the date passed with current timestamp.
     *
     * @todo Remove when Carbon package is upgraded to >=2.0
     *
     * @param string $timestamp
     * @param bool   $absolute Get the absolute of the difference
     *
     * @return int
     */
    public function diffInMilliseconds(string $timestamp, $absolute = true)
    {
        $microsecondsPerSecond = 1000000;

        $microsecondsPerMillisecond = 1000;

        $now = Carbon::now();

        $hbTimestamp = Carbon::parse($timestamp);

        $diff = $now->diff($hbTimestamp);

        try {
            $value = (int)round(((((($diff->days * Carbon::HOURS_PER_DAY) +
                        $diff->h) * Carbon::MINUTES_PER_HOUR +
                        $diff->i) * Carbon::SECONDS_PER_MINUTE +
                        ($diff->f + $diff->s)) * $microsecondsPerSecond) / $microsecondsPerMillisecond);
        }
        catch (\Throwable $e)
        {
            // tracing it as info to reduce the noise in case of exception
            // will be removed once the issue is fixed
            $this->trace->info(
                TraceCode::HEARTBEAT_CHECK_TIME_CONVERSION,
                [
                    'message'             => 'exception',
                    'error'               => $e->getMessage(),
                    'diff'                => $diff,
                ]);

            // Setting this as 0. which will evaluate to no lag
            $value = 0;
        }

        return $absolute || !$diff->invert ? $value : -$value;
    }

    protected function traceConnectionSelection(string $traceCode, bool $useSlave, array $extra = [])
    {
        $connection = ($useSlave === true) ? Metric::SLAVE : Metric::MASTER;

        $this->trace->info(
            $traceCode,
            [
                'lag'                       => $this->lag,
                'mock'                      => $this->mock,
                'connection'                => $connection,
                'traffic_percentage'        => $this->trafficPercent,
                'random_traffic_percentage' => $this->randomTrafficPercent,
            ] + $extra);
    }

    /**
     *
     * We fetch 5 things from Cache:
     * 1. heartbeat_enabled - Check if heartbeat is enabled or not
     * 2. heartbeat_time_threshold - Get the threshold over which the time delta is considered to be a lagging
     * 3  heartbeat_slave_time_threshold - Get the threshold over which the time delta is considered to be a lagging
     *    in case of connection identifier is `slave`
     * 4. heartbeat_traffic_percent - What percentage of traffic we need to move to master for `heartbeat_routes`
     * 5. heartbeat_mock - Mock heartbeat (only log to sumologic and don't take any action)
     *
     * it'll load the values to corresponding class variables
     * also does type casing for required fields
     */
    private function loadConfigs()
    {
        $heartbeatConfig = $this->cache->many([
            $this->config['mock'],
            $this->config['enabled'],
            $this->config['time_threshold'],
            $this->config['slave_time_threshold'],
            $this->config['traffic_percentage'],
        ]);

        list(
            $this->mock,
            $this->enabled,
            $this->timeThreshold,
            $this->slaveTimeThreshold,
            $this->trafficPercent,
            ) = array_values($heartbeatConfig);

        $this->mock = (bool) $this->mock;

        $this->enabled = (bool) $this->enabled;
    }

    /**
     * it will initialize the connection resolver for
     * - master
     * - slave
     * - heartbeat
     * this will also check the lag based in custom threshold for required identifier
     * this will give a closure which can then be called by calling method
     *
     * all the closures return
     * - true if slave to be used
     * - false if master to be used
     */
    private function initializeConnectionResolvers()
    {
        $this->connectionResolver = [
            self::MASTER => function ($readPdo): bool
            {
                return false;
            },

            self::SLAVE  => function ($readPdo): bool
            {
                return ($this->isSlaveLagging($readPdo, $this->slaveTimeThreshold) === false);
            },

            self::HEARTBEAT => function ($readPdo): bool
            {
                return ($this->isSlaveLagging($readPdo, $this->timeThreshold) === false);
            },
        ];
    }

    /**
     * It will do a mock check based on this it sends whether to use slave or master
     * @param bool $useSlave
     * @return bool
     */
    private function finalizeResult(bool $useSlave): bool
    {
        // If mock flag is set then ignore the heartbeat result
        if ($this->mock === true)
        {
            $useSlave = true;
        }

        return $useSlave;
    }
}
