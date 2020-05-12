<?php

namespace RZP\Base\Database;

use App;

use Illuminate\Support\Facades\File;
use Razorpay\Trace\Logger as Trace;

use RZP\Constants\Environment;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;

class Config
{
    const DATABASE_CONFIG       = 'database.connections';

    const PROXY_SQL_CONFIG      = 'proxy_sql_unix_socket';

    const PROXY_SQL_ENABLE      = 'proxy_sql_enable';

    const IS_WORKER_POD         = 'is_worker_pod';

    const WORKER_CONFIG         = 'worker';

    const ENABLE                = 'enable';

    const TEST                  = 'test';

    const DISABLE               = 'disable';

    const PROXY_CONNECTIONS     = [
        'live',
        'test',
        'slave-test',
        'slave-live',
    ];

    public $isProxySqlActive;

    protected $app;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        // If middleware proxysql is removed or not called,
        // proxysql is assumed disabled because of this.
        $this->isProxySqlActive = false;
    }

    public function setDatabaseHostsIfApplicable()
    {
        $this->isProxySqlActive = $this->canUseProxySql();

        if ($this->isProxySqlActive === false)
        {
            return;
        }

        $app = $this->app;

        $configs = $app['config']->get(self::DATABASE_CONFIG);

        $proxySqlSocket = $this->app['config']->get(self::DATABASE_CONFIG . '.' . self::PROXY_SQL_CONFIG);

        try
        {
            foreach ($configs as $connectionName => $config)
            {
                if ((in_array($connectionName, self::PROXY_CONNECTIONS, true) === true) and
                    (is_array($config) === true))
                {
                    // by default if port and unix_socket both are present. Laravel will prioritise socket over port
                    if (empty($config['read']) === false)
                    {
                        $app['config']->set(self::DATABASE_CONFIG . '.' . $connectionName . '.read.unix_socket', $proxySqlSocket);
                    }

                    if (empty($config['write']) === false)
                    {
                        $app['config']->set(self::DATABASE_CONFIG . '.' . $connectionName . '.write.unix_socket', $proxySqlSocket);
                    }

                    if (empty($config['host']) === false)
                    {
                        $app['config']->set(self::DATABASE_CONFIG . '.' . $connectionName . '.unix_socket', $proxySqlSocket);
                    }
                }
            }
        }
        catch(\Throwable $ex)
        {
            $app['trace']->traceException($ex);
        }
    }

    public function unsetSocketFromDatabaseConfig($name)
    {
        $app = $this->app;

        if ($app['config']->has(self::DATABASE_CONFIG . '.' . $name . '.read') === true)
        {
            $app['config']->set(self::DATABASE_CONFIG . '.' . $name . '.read.unix_socket', null);
        }

        if ($app['config']->has(self::DATABASE_CONFIG . '.' . $name . '.write') === true)
        {
            $app['config']->set(self::DATABASE_CONFIG . '.' . $name . '.write.unix_socket', null);
        }

        if ($app['config']->has(self::DATABASE_CONFIG . '.' . $name . '.host') === true)
        {
            $app['config']->set(self::DATABASE_CONFIG . '.' . $name . '.unix_socket', null);
        }
    }

    public function isProxySqlActive()
    {
        return $this->isProxySqlActive === true;
    }

    protected function canUseProxySql()
    {
        $waitTimeoutActive = $this->app['db.connector.mysql']->isWaitTimeoutActive();

        // if wait timeout is already enabled then do not use proxySQL
        if ($waitTimeoutActive === true)
        {
            return false;
        }

        $proxySqlSocket = $this->app['config']->get(self::DATABASE_CONFIG . '.' . self::PROXY_SQL_CONFIG);

        // cron env and workers will not have this file.
        if ((empty($proxySqlSocket) === true) or (file_exists($proxySqlSocket) === false))
        {
            return false;
        }

        // Worker only makes a db connection once. So we will not need proxySQL for this.
        // (Not needed but this is just extra security.)
        $isWorkerPod = $this->app['config']->get(self::WORKER_CONFIG . '.' . self::IS_WORKER_POD);

        if ($isWorkerPod === true)
        {
            return false;
        }

        if ($this->app->environment(Environment::AUTOMATION) === true)
        {
            return true;
        }

        if ($this->app->environment(Environment::PRODUCTION) === false)
        {
            return false;
        }

        // this is kept to rollback at later stage
        // we can just change env and re deploy to disable proxysql.
        $proxySqlEnable = $this->app['config']->get(self::DATABASE_CONFIG . '.' . self::PROXY_SQL_ENABLE);

        if ($proxySqlEnable ===  false)
        {
            return false;
        }

        $fileConfig = $this->getProxySqlConfig();

        // this is used to ramp up each server at a time.
        if ($fileConfig === self::ENABLE)
        {
            return true;
        }

        $mode = (empty($this->app['request.ctx']) === true) ? Mode::LIVE : $this->app['request.ctx']->getMode();

        if (($fileConfig === self::TEST) && ($mode === Mode::TEST))
        {
            return true;
        }

        return false;
    }

    protected function getProxySqlConfig()
    {
        try
        {
            $value = File::get(base_path() . '/database/proxy_sql', true);
            $value = preg_replace('/\s+/', ' ', $value);
            $value = preg_replace('/\s+/', '', $value);
            return $value;
        }
        catch(\Throwable $ex)
        {
            $this->app['trace']->traceException(
                $ex,
                Trace::WARNING,
                TraceCode::DB_PROXY_SQL_FILE_READ_FAILED);

            return self::DISABLE;
        }
    }
}

