<?php

namespace RZP\Base\Database\LagChecker;

use App;
use Closure;
use Carbon\Carbon;
use Illuminate\Redis\RedisManager;

use Razorpay\Trace\Logger as Trace;

use RZP\Constants\Mode;
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
     * @var string
     */
    protected $mode;

    /**
     * @var int
     */
    protected $lag;

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

        $this->mode = $app['rzp.mode'] ?? Mode::LIVE;;
    }

    /**
     * {@inheritDoc}
     */
    public function useReadPdoIfApplicable($readPdo)
    {
        $useSlave = false;

        try
        {
            $useSlave = $this->checkHeartbeat($readPdo);
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

    protected function checkHeartbeat($readPdo): bool
    {
        $useSlave = true;

        //
        // We wont be checking heartbeat for test mode
        //
        if ($this->mode === null)
        {
            return $useSlave;
        }

        //
        // We fetch 4 things from Redis:
        // 1. heartbeat_enabled - Check if heartbeat is enabled or not
        // 2. heartbeat_time_threshold - Get the threshold over which the time delta is considered to be a lagging
        // 3. heartbeat_traffic_percent - What percentage of traffic we need to move to master for `heartbeat_routes`
        // 4. heartbeat_mock - Mock heartbeat (only log to sumologic and don't take any action)
        //
        $heartbeatConfig = $this->cache->many([
            $this->config['enabled'],
            $this->config['time_threshold'],
            $this->config['traffic_percentage'],
            $this->config['mock'],
        ]);

        list($enabled, $timeThreshold, $trafficPercent, $mock) = array_values($heartbeatConfig);
        //
        // If pt-heartbeat is disabled then useSlave
        //
        if ((bool) $enabled === false)
        {
            return $useSlave;
        }

        //
        // If the lag is not more than the time threshold set then continue using read connection
        //
        $isSlaveLagging = $this->isSlaveLagging($readPdo, $timeThreshold);

        if ($isSlaveLagging === false)
        {
            $this->traceConnectionSelection(TraceCode::HEARTBEAT_CHECK_NO_LAG, $useSlave);

            return $useSlave;
        }

        //
        // Check if there are any whitelisted route that need to be routed
        // to master basis $trafficPercent weight.
        //
        $useSlave = $this->resolveConnectionForRoute($trafficPercent);

        $this->traceConnectionSelection(TraceCode::HEARTBEAT_CHECK_COMPLETED, $useSlave);

        if ((bool) $mock === true)
        {
            $useSlave = true;
        }

        return $useSlave;
    }

    /**
     * It will check and evaluates the probability based on the percentage set.
     * If passes then it does the route filter.
     * If the current route pattern matches the whitelisted routes then returns true,
     *
     * @param $trafficPercent
     *
     * @return bool
     */
    protected function resolveConnectionForRoute($trafficPercent): bool
    {
        $useSlave  = true;

        $useMaster = false;

        $routesLength = $this->redis->scard($this->config['routes']);

        //
        // If no routes have been set then move all traffic to master
        //
        if ($routesLength === 0)
        {
            $this->traceConnectionSelection(TraceCode::HEARTBEAT_CHECK_EMPTY_ROUTE_LIST, $useMaster);

            return $useMaster;
        }

        $currentRoute = $this->reqCtx->getRoute();

        $routeExists = (bool) $this->redis->sismember($this->config['routes'], $currentRoute);

        //
        // If the current route doesn't exist (but `routes` have some members)
        // then move traffic to slave as its importance is less
        //
        if ($routeExists === false)
        {
            $this->traceConnectionSelection(TraceCode::HEARTBEAT_CHECK_ROUTE_NOT_LISTED, $useSlave);

            return $useSlave;
        }
        else
        {
            // Route exists inside `routes` and the random weight is less than threshold
            // then move traffic to master
            if ($this->randomTrafficPercent <= $trafficPercent)
            {
                $this->traceConnectionSelection(TraceCode::HEARTBEAT_CHECK_RAMP_RESULT, $useMaster);

                return $useMaster;
            }

            $this->traceConnectionSelection(TraceCode::HEARTBEAT_CHECK_RAMP_RESULT, $useSlave);
        }

        return $useSlave;
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

//        $result['diff_in_code'] = $this->diffInMilliseconds($result['ts']);

        $status = ($this->lag <= $threshold)? false : true;

//        $this->traceConnectionSelection(TraceCode::HEARTBEAT_LAG_CHECK_DEBUG_TRACE, !$status, $result);

        return $status;
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
                'connection' => $connection,
                'lag'        => $this->lag,
            ] + $extra);
    }
}
