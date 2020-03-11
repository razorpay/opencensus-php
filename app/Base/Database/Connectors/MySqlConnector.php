<?php

namespace RZP\Base\Database\Connectors;

use App;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Connectors\MySqlConnector as BaseMySqlConnector;

use RZP\Constants\Metric;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class MySqlConnector extends BaseMySqlConnector
{
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
    protected $waitTiemout;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function connect(array $config)
    {
        $connection = parent::connect($config);

        $this->initializeWaitTimeout($connection, $config);

        return $connection;
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
        $this->app['trace']->info(TraceCode::DB_EXECUTING_WAIT_TIMEOUT, [
                'timeout_value' => $timeoutValue,
             ]);

        $connection->exec("set session wait_timeout={$timeoutValue}");
    }

    public function recordTransactionDuration($duration)
    {
        if ($this->isWaitTimeoutEnabled() === false)
        {
            return;
        }

        $db = $this->getDB();

        if ($db->transactionLevel() > 0)
        {
            return;
        }

        $this->app['trace']->histogram(Metric::TRANSACTION_DURATION_MILLISECONDS, $duration, []);
    }

    public function setWaitTimeout($type, $conn = '')
    {
        if ($this->isWaitTimeoutEnabled() === false)
        {
            return;
        }

        $db = $this->getDb($conn);

        // if there is a transaction already started we don't want to proceed
        if ($db->transactionLevel() > 0)
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
            if ($this->canReconnect($ex) === true)
            {
                $db->reconnect();

                $this->execWaitTimeout($db->getPdo(), $timeoutValue);

                return;
            }

            $this->app['trace']->traceException($ex, null, TraceCode::WAIT_TIMEOUT_EXCEPTION,[
                    'type' =>$type,
            ]);

            throw $ex;
        }
    }

    protected function canReconnect($ex)
    {
        $message = $ex->getMessage();

        return Str::contains($message, [
            'server has gone away',
            'Error while sending QUERY packet',
            'query_wait_timeout'
        ]);
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
        if (empty($this->waitTimeout) === true)
        {
            $this->waitTimeout = $this->getWaitTimeoutConfig();
        }

        return ($this->waitTimeout === self::ENABLE);
    }
}

