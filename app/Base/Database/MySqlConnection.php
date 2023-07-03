<?php

namespace RZP\Base\Database;

use App;
use Cache;
use Closure;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Database\QueryException;
use Razorpay\Trace\Facades\Trace as TraceFacade;
use Illuminate\Database\MySqlConnection as BaseMySqlConnection;

use RZP\Trace\TraceCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Exception\LogicException;
use RZP\Base\Database\LagChecker;

class MySqlConnection extends BaseMySqlConnection
{
    use DetectsLostConnections;

    /**
     * LagChecker object to determine which pdo connection to use
     * @var LagChecker\LagChecker
     */
    protected $lagChecker;

    /**
     * LagChecker object to determine which pdo connection to use
     * @var LagChecker\LagChecker
     */
    protected $heartbeatLagChecker;

    /**
     * Flag to force use the read connection overriding the sticky config.
     * @var boolean
     */
    public $forceReadPdo;

    /**
     * @var Trace
     */
    protected $trace;

    /**
     * Indicates if the read PDO connection needs to be reevaluated for replica lag.
     * @var boolean
     */
    protected $forceCheckReplicaLag;

    /**
     * If set, heartbeat lag check will run regardless of master percentage check
     * and it'll only collect state rather than deciding the connection
     * @var bool
     */
    protected $heartbeatForceRun = false;

    /**
     * if set to true it'll make sure that readPdo method is called on parent
     * using static variable to hold this information as
     * in case of reconnect laravel creates new instance of MySqlConnection
     *
     * Currently reconnect happens only in case if heartbeat so
     * in reconnect is happens then its for a read replica connection to check the lag
     *
     * @var bool
     */
    protected static $callParent = false;

    /**
     * Holds the previously established read pdo connection if any, for usage later once replication lag is resolved.
     * @var mixed
     */
    protected $previousReadPdo;

    protected $isSlaveRoute;

    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        parent::__construct($pdo, $database, $tablePrefix, $config);

        $this->trace = TraceFacade::getFacadeRoot();

        $lagCheckConfig = $config['lag_check'];

        $this->forceReadPdo = false;

        $this->lagChecker = $this->getLagChecker($lagCheckConfig);

        $heartbeatCheckConfig = $config['heartbeat_check'];

        $this->heartbeatForceRun = $heartbeatCheckConfig['force_run'];

        $this->heartbeatLagChecker = $this->getLagChecker($heartbeatCheckConfig);

        $this->forceCheckReplicaLag = false;

        $this->previousReadPdo = null;

        $this->isSlaveRoute = $this->validateSlaveConnectionRoute();
    }

    /*
     * validateSlaveConnectionRoute checks whether to use slave connection irrespective of
     * lag in slave instance.
     */
    protected function validateSlaveConnectionRoute()
    {
        return $this->canRouteToSlave();
    }

    protected function canRouteToSlave()
    {
       $app = App::getFacadeRoot();

       $routeName = $app['request.ctx']->getRoute() ?? null;

       if (empty($routeName) === true)
       {
            return false;
       }

       return $this->checkStaticRouteList($routeName);
    }

    protected function checkStaticRouteList($routeName)
    {
        $routes = \RZP\Http\Route::$forceReplicaRoutes;

        return (in_array($routeName, $routes, true) === true);
    }

    protected function tryAgainIfCausedByLostConnection(QueryException $e, $query, $bindings, Closure $callback)
    {
        if ($this->causedByLostConnection($e->getPrevious()))
        {
            $dbConfig = $this->getConfig();

            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::DB_CONNECTION_FAILED_RETRYING_CONNECTION,
                [
                    'query' => true,
                    'name'  => $dbConfig['name'] ?? '',
                    'func' => 'MySqlConnection::tryAgainIfCausedByLostConnection',
                ]
            );

            // Commenting this as we want to move to reconnect back to proxySQL
            // App::getFacadeRoot()['proxysql.config']->unsetSocketFromDatabaseConfig($this->getName());
            // App::getFacadeRoot()['proxysql.config']->resetDatabaseConnectionHostAndPort($this->getName());

            try
            {
                $this->reconnect();

                return $this->runQueryCallback($query, $bindings, $callback);
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex,
                    Trace::ERROR,
                    TraceCode::RECONNECT_FAILED_AFTER_DB_CONNECTION_FAILURE,
                    [
                        'func' => 'MySqlConnection::tryAgainIfCausedByLostConnection',
                    ]
                );

                throw $ex;
            }
        }

        $this->trace->traceException($e,
            Trace::ERROR,
            TraceCode::DB_CONNECTION_ERROR_WITH_NO_RETRY,
            [
                'func' => 'MySqlConnection::tryAgainIfCausedByLostConnection',
            ]
        );

        throw $e;
    }

    /**
     * @param array $config
     * @return LagChecker\HeartbeatLagChecker|LagChecker\RedisLagChecker
     * @throws LogicException
     */
    protected function getLagChecker(array $config)
    {
        $driver = $config['driver'];

        switch ($driver)
        {
            case 'redis':
                return new LagChecker\RedisLagChecker($config);

            case 'heartbeat':
                $heartbeat = new LagChecker\HeartbeatLagChecker($config);

                $heartbeat->setReconnector(function (\Exception $e, $mode)
                {
                    if ($this->causedByLostConnection($e) === true)
                    {
                        //
                        // Make sure that it calls parent::getReadPdo() from reconnect method
                        // Else it will get stuck in recursion
                        //
                        static::$callParent = true;

                        $connection = app('db')->reconnect($mode);

                        $this->trace->count(Metric::DATABASE_RECONNECT, [
                            'mode'      => $mode,
                            'connected' => ($connection->readPdo !== null),
                        ]);

                        return $connection->readPdo;
                    }

                    return null;
                });

                return $heartbeat;

            default:
                throw new LogicException('LagChecker driver not implemented: ' . $driver);
        }
    }

    public function getReadPdo()
    {
        try
        {
            //
            // If there is an active transaction, we always want
            // to use the master connection.
            //
            if ($this->transactions > 0)
            {
                return $this->getPdo();
            }

            //
            // If a DML query has been executed in the request and 'sticky' config
            // is true and we are not force using the read pdo and request path is not a slaveRoute,
            // then always use the master connection
            //
            if (($this->getConfig('sticky') === true) and
                ($this->recordsModified === true) and
                ($this->forceReadPdo === false) and
                ($this->isSlaveRoute === false))
            {
                return $this->getPdo();
            }

            //
            // When the pdo connection to replica is going to get established the
            // first time, or an established pdo connection needs to be rechecked
            // for replica lag (long running queue workers),use the lagChecker to
            // determine whether to establish the connection or not.
            //
            if (($this->readPdo instanceof Closure) or
                ($this->forceCheckReplicaLag === true))
            {
                //
                // If we had used a readPdo connection previously, use that to
                // check the replica lag, as the current readPdo connection will either
                // be the write connection or null, depending on previous lag checks
                //
                if ($this->previousReadPdo !== null)
                {
                    $this->readPdo = $this->previousReadPdo;
                }

                //
                // Currently this is done to avoid doing recursive call
                // We are calling getReadPdo while we reconnect which might go into recursion
                // Only in case of reconnect this will be set to true
                //
                if (static::$callParent === true)
                {
                    static::$callParent = false;

                    return parent::getReadPdo();
                }

                $result = $this->shouldUseSlave($this->readPdo);

                //
                // If the lag checker returns null, i.e read connection is not to be used,
                // store the current connection in $previousReadPdo so that it
                // can be used to check lag in the future. (Useful for queue workers)
                //
                if ($result === null)
                {
                    $this->previousReadPdo = $this->readPdo;
                }
                else if ($result instanceof Closure)
                {
                    $result = call_user_func($result);
                }

                //
                // Reset this to false here, so that the lag check is not
                // evaluated again on subsequent selects.
                //
                $this->forceCheckReplicaLag = false;

                $this->readPdo = $result;
            }

            return $this->readPdo ?: $this->getPdo();
        }
        catch (\Throwable $ex)
        {
            //
            // If there is any exception in setting up the replica connection,
            // we trace it and fallback to the master connection.
            //
            $this->trace->traceException($ex, Trace::CRITICAL, TraceCode::DB_READ_CONN_SETUP_ERROR);

            //
            // The previousReadPdo is set to readPdo here, so that on subsequent,
            // selects the lag check can be evaluated when forceCheckReplicaLag
            // is set to true again.
            //
            $this->previousReadPdo = $this->readPdo;

            //
            // Setting the readPdo to the master connection here, so that on further
            // selects in the same request, the lag check is not evaluated again.
            //
            $this->readPdo = $this->getPdo();

            return $this->readPdo;
        }
    }

    public function forceReadPdo(bool $value)
    {
        $this->forceReadPdo = $value;
    }

    /**
     * checks `skip_slave` flag set in redis. if true then returns null.
     * else checks for heartbeat check and decides on connection
     *
     * @param $readPdo
     *
     * @return mixed|null|\PDO|LagChecker\Closure
     */
    protected function shouldUseSlave($readPdo)
    {
        // First check if `skip_slave` flag in Redis is set to true or not.
        // If true then use write PDO object and move all traffic to master.
        $result = $this->lagChecker->useReadPdoIfApplicable($readPdo);

        if (($result === null) and
            ($this->heartbeatForceRun === false) and
            ($this->isSlaveRoute === false))
        {
            return null;
        }

        /**
         * foceReadPdo is set to true in case of useSlave(callable $callback), if it's true then
         * queries has to be run on slave. Since skip slave condition is evaluated
         * above we can safely route queries to slave. OR for certain router we want to ensure that all the read
         * queries goes to slave irrespective of lag in this case isSlaveRoute will be set to true.
         */
        if (($this->forceReadPdo === true) or
            ($this->isSlaveRoute === true))
        {
            return $readPdo;
        }

        // If the Redis lagchecker is not in effect then we'll go ahead with
        // pt-heartbeat lag checker.
        $heartbeatResult = $this->heartbeatLagChecker->useReadPdoIfApplicable($readPdo);

        $connection = ($heartbeatResult === null) ? Metric::MASTER : Metric::SLAVE;

        $this->trace->count(Metric::ENFORCE_MASTER_CONNECTION, [
            Metric::CONNECTION => $connection
        ], 1);

        $result = ($this->heartbeatForceRun === true) ?
            $result : $heartbeatResult;

        return $result;
    }

    /**
     * Resets some attributes used to maintain the connection state,
     * without explicitly recreating the connection object. This is
     * mainly useful for queue processes, where we want to reuse the
     * same connection object.
     */
    public function resetConnectionAttributes()
    {
        // Reset record of previous DML operations made using this connection.
        $this->recordsHaveNotBeenModified();

        // Reset the forceReadPdo flag to false if previously set to true.
        $this->forceReadPdo(false);

        //
        // Sets this flag to true, so that the read pdo connection is evaluated
        // again for replica lag.
        //
        $this->forceCheckReplicaLag = true;
    }

    /**
     * This function is complementary to the `recordsHaveBeenModified` method
     * in the parent class. It only sets the `recordsModified` flag to false if
     * it was previously set to true.
     *
     * @param  bool|boolean $value
     */
    protected function recordsHaveNotBeenModified(bool $value = false)
    {
        if ($this->recordsModified === true)
        {
            $this->recordsModified = $value;
        }
    }

    public function setSlaveRoute(bool $value)
    {
        $this->isSlaveRoute = $value;
    }

    public function setForceCheckReplicaLag(bool $value)
    {
        $this->forceCheckReplicaLag = $value;
    }

    public function setLagChecker($lagChecker)
    {
        $this->lagChecker = $lagChecker;
    }

    public function setForceReadPdo(bool $value)
    {
        $this->forceReadPdo = $value;
    }

    public function isRecordsModified(bool $value)
    {
        $this->recordsModified = $value;
    }

    public function setTransaction(int $value)
    {
        $this->transactions = $value;
    }
}
