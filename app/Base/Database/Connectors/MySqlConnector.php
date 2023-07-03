<?php

namespace RZP\Base\Database\Connectors;

use App;
use Redis;
use Route;
use Database\Connection;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Connectors\MySqlConnector as BaseMySqlConnector;

use PDO;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Tracing;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\ServerErrorException;
use RZP\Base\Database\DetectsLostConnections;
use RZP\Services\CircuitBreaker\CircuitBreaker;
use OpenCensus\Trace\Integrations\PDO as PDOTracer;
use RZP\Services\CircuitBreaker\Store\RedisClusterStore;

class MySqlConnector extends BaseMySqlConnector
{
    use DetectsLostConnections;

    const ENABLE  = 'enable';
    const DISABLE = 'disable';

    const TYPE_WAIT_TIMEOUT             = 'wait_timeout';
    const TYPE_TRANSACTION_WAIT_TIMEOUT = 'transaction_wait_timeout';

    /**
    * The application instance.
    *
    * @var \Illuminate\Foundation\Application
    */
    protected $app;

    // wait timeout config
    protected $waitTimeout;

    /*
     * Circuit breaker instance
     */
    protected $cb = null;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function connect(array $config)
    {
        $socketConnection = (empty($config['unix_socket']) === false);

        $this->initiateAndCheckCircuitBreaker();

        $proxysqlActive = $this->app['proxysql.config']->isProxySqlActive();

        try
        {
            $connection = parent::connect($config);

            $this->markCircuitBreakerSuccess();
        }
        catch (Exception $e)
        {
            if ($this->causedByLostConnection($e) === true)
            {
                // Commenting this as we want to move to reconnect back to proxySQL
                // $this->app['proxysql.config']->unsetSocketFromDatabaseConfig($config['name']);
                // $this->app['proxysql.config']->resetDatabaseConnectionHostAndPort($config['name']);
                // unset($config['unix_socket']);

                $this->app['trace']->traceException($e,
                    Trace::ERROR,
                    TraceCode::DB_CONNECTION_FAILED_RETRYING_CONNECTION,
                    [
                        'name'  => $config['name'] ?? '',
                        'func' => 'MySqlConnector::connect',
                    ]
                );

                // connection retry
                try
                {
                    $connection = parent::connect($config);
                }
                catch (\Exception $ex)
                {
                    $this->app['trace']->traceException($ex,
                        Trace::ERROR,
                        TraceCode::RECONNECT_FAILED_AFTER_DB_CONNECTION_FAILURE,
                        [
                            'func' => 'MySqlConnector::connect',
                        ]
                    );

                    throw $ex;
                }
            }
            else
            {
                $this->markCircuitBreakerFailure();

                $this->app['trace']->traceException($e,
                    Trace::ERROR,
                    TraceCode::DB_CONNECTION_ERROR_WITH_NO_RETRY,
                    [
                        'func' => 'MySqlConnector::connect',
                    ]
                );

                throw $e;
            }
        }

        // Load PDO tracer here for automatically trace all db calls
        // It is placed here to be able to trace queries on multiple dbs separately
        if ((Tracing::isEnabled($this->app) === true) and
            (Tracing::shouldTraceRoute(Route::current()) === true))
        {
            $hostDSN = $this->getHostDsn($config);
            $options = [
                'tags' => [
                    'proxy_sql' => $this->hasSocket($config),
                ]
            ];

            PDOTracer::load($hostDSN, $options);
        }

        $this->initializeWaitTimeout($connection, $config);

        if ((empty($config['name']) === false) and
            (($config['name'] === Connection::DATA_WAREHOUSE_LIVE) or ($config['name'] === Connection::DATA_WAREHOUSE_TEST)))
        {
            $this->initializeTiDBSessionVariables($connection, $config);
        }

        return $connection;
    }

    protected function initiateAndCheckCircuitBreaker()
    {
        if ($this->app->runningInConsole() === true)
        {
            $serviceName = 'worker_db';

            $redis = Redis::connection('throttle')->client();

            $store = new RedisClusterStore($redis);

            $this->cb = new CircuitBreaker($store, $serviceName);

            if ($this->cb->isAvailable() === false)
            {
                $this->app['trace']->info(TraceCode::CIRCUIT_BREAKER_OPEN, [
                    'total_failures' => $this->cb->getFailuresCounter(),
                ]);

                throw new ServerErrorException(
                    'DB Circuit Breaker Open',
                    ErrorCode::SERVER_ERROR);
            }
        }
    }

    protected function markCircuitBreakerSuccess()
    {
        if ($this->cb !== null)
        {
            $this->cb->success();
        }
    }

    protected function markCircuitBreakerFailure()
    {
        if ($this->cb !== null)
        {
            $this->cb->failure();
        }
    }

    protected function getWaitTimeoutConfig()
    {
        try
        {
            $value = File::get(base_path() . '/database/wait_timeout', true);
            $value = preg_replace('/\s+/', ' ', $value);
            $value = preg_replace('/\s+/', '', $value);
            return $value;
        }
        catch(\Throwable $ex)
        {
            $this->app['trace']->traceException(
            $ex,
            Trace::WARNING,
            TraceCode::DB_WAIT_TIMEOUT_FILE_READ_FAILED);

            return self::DISABLE;
        }
    }

    protected function initializeWaitTimeout($connection, array $config)
    {
        if (($this->isWaitTimeoutEnabled() === true) and
            (empty($config[self::TYPE_WAIT_TIMEOUT]) === false))
        {
            $this->execWaitTimeout($connection, $config[self::TYPE_WAIT_TIMEOUT]);
        }
    }

    protected function execWaitTimeout($connection, $timeoutValue)
    {
        $connection->exec("set session wait_timeout={$timeoutValue}");
    }

    protected function initializeTiDBSessionVariables($connection, array $config)
    {
        foreach($config as $key => $value)
        {
            if ((Str::startsWith($key, 'tidb_')) and (empty($config[$key]) === false))
            {
                try
                {
                    $connection->exec("set session {$key}='{$value}'");
                }
                catch(\Throwable $ex)
                {
                    $this->app['trace']->traceException($ex, null, TraceCode::DB_SESSION_VAR_SETUP_ERROR,[
                        'key'   => $key,
                        'value' => $value,
                    ]);
                }
            }
        }
    }

    public function checkAndReloadDBIfCausedByLostConnection($ex, $conn = '')
    {
        if($this->causedByLostConnection($ex))
        {
            $db = $this->getDB($conn);

            $db->reconnect();

            return true;
        }
        return false;
    }

    public function setWaitTimeout($type, $conn = '')
    {
        $db = $this->getDb($conn);

        // if there is a transaction already started we don't want to proceed
        if ($db->transactionLevel() > 0)
        {
            return;
        }

        if ($this->isWaitTimeoutEnabled() === false)
        {
            return;
        }

        $config = $this->getDbConfig($conn);

        if (empty($config[$type]) === true)
        {
            return;
        }

        $timeoutValue = $config[$type];

        try
        {
            $this->execWaitTimeout($db->getPdo(), $timeoutValue);
        }
        catch(\Throwable $ex)
        {
            if ($this->causedByLostConnection($ex) === true)
            {
                $db->reconnect();

                $this->execWaitTimeout($db->getPdo(), $timeoutValue);

                return;
            }

            $this->app['trace']->traceException($ex, null, TraceCode::WAIT_TIMEOUT_EXCEPTION, [
                'type' => $type,
            ]);

            throw $ex;
        }
    }

    protected function getDB($conn = '')
    {
       if (empty($conn) === true)
       {
           $conn = $this->getDefaultDbConn();
       }

       return $this->app['db']->connection($conn);
    }

    protected function getDefaultDbConn()
    {
        return $this->app['config']->get('database.default');
    }

    public function getDbConfig($conn = '')
    {
        if (empty($conn) === true)
        {
            $conn = $this->getDefaultDbConn();
        }

        return $this->app['config']->get('database.connections.' . $conn);
    }

    protected function isWaitTimeoutEnabled()
    {
        $proxysqlActive = $this->app['proxysql.config']->isProxySqlActive();

        if ($proxysqlActive === true)
        {
            return false;
        }

        if (empty($this->waitTimeout) === true)
        {
            $this->waitTimeout = $this->getWaitTimeoutConfig();
        }

        return ($this->waitTimeout === self::ENABLE);
    }

    public function isWaitTimeoutActive()
    {
        return ($this->waitTimeout === self::ENABLE);
    }

    protected function setModes(PDO $connection, array $config)
    {
        if (isset($config['modes'])) {
            $this->setCustomModes($connection, $config);
        } elseif (isset($config['strict'])) {
            if ($config['strict']) {
                $connection->exec($this->strictMode($connection, $config));
            } else {
                $connection->exec("set session sql_mode='NO_ENGINE_SUBSTITUTION'");
            }
        }
    }

    public function getReplicationLagInMilli(string $connection)
    {
        $query = 'SELECT ROUND(( ROUND(UNIX_TIMESTAMP(Now(6)) * 1000000) - (
                        UNIX_TIMESTAMP(SUBSTR(ts, 1, 19)) * 1000000 +
                        SUBSTR(ts, 21, 6) )
                     ) / 1000) AS replica_lag_milli
                FROM heartbeat ORDER BY ts DESC
                LIMIT  1';

        $result = $this->app['db']->connection($connection)->selectOne($query);

        return $result->replica_lag_milli;
    }
}
