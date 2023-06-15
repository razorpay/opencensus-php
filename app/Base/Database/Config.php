<?php

namespace RZP\Base\Database;

use App;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Constants\Environment;

class Config
{
    const DATABASE_CONFIG       = 'database.connections';

    const PROXY_SQL_SIDECAR_CONFIG = 'proxy_sql_unix_socket';

    const PROXY_SQL_SERVICE_CONFIG = 'proxy_sql_service_config';

    const PROXY_SQL_ENABLE_PAYMENT_FETCH_REPLICA = 'proxy_sql_enable_payment_fetch_replica';

    const PROXY_SQL_ENABLE      = 'PROXY_SQL_ENABLE';

    const IS_WORKER_POD         = 'is_worker_pod';

    const WORKER_CONFIG         = 'worker';

    const ENABLE                = 'enable';

    const PROXYSQL_SIDECAR      = 'proxysql_sidecar';

    const PROXYSQL_SERVICE      = 'proxysql_service';

    const TEST                  = 'test';

    const DISABLE               = 'disable';

    public array $proxyConnections;

    public bool $isProxySqlSidecarActive;

    public bool $isProxySqlServiceActive;

    public array $originalConnectionConfig;

    protected $app;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        // If middleware proxysql is removed or not called,
        // proxysql is assumed disabled because of this.
        $this->isProxySqlSidecarActive = false;

        $this->isProxySqlServiceActive = false;

        $this->proxyConnections = [
            'live',
            'test',
            'slave-test',
            'slave-live',
        ];
    }

    public function setDatabaseHostsIfApplicable()
    {
        $waitTimeoutActive = $this->app['db.connector.mysql']->isWaitTimeoutActive();

        // if wait timeout is already enabled then do not use proxySQL
        if ($waitTimeoutActive === true)
        {
            return;
        }

        // Worker only makes a db connection once. So we will not need proxySQL for this.
        // (Not needed but this is just extra security.)
        // Commenting this to allow worker db connections via proxySQL
//        $isWorkerPod = $this->app['config']->get(self::WORKER_CONFIG . '.' . self::IS_WORKER_POD);
//
//        if ($isWorkerPod === true)
//        {
//            return;
//        }

        // this is kept to rollback at later stage
        // we can just change env and re-deploy to disable proxysql.
        // values of PROXY_SQL_ENABLE can be:
        // disable - to disable proxysql.
        // enable or proxysql_sidecar - to enable proxysql sidecar.
        // proxysql_service - to enable proxysql service.
        // test - if the mode is test, to enable proxysql service or sidecar.
        $proxySqlEnable = env(self::PROXY_SQL_ENABLE, self::DISABLE);

        if($proxySqlEnable === self::DISABLE)
        {
            return;
        }

        $mode = (empty($this->app['request.ctx']) === true) ? Mode::LIVE : $this->app['request.ctx']->getMode();

        $this->isProxySqlServiceActive = $this->canUseProxySqlService($proxySqlEnable, $mode);

        $this->isProxySqlSidecarActive = $this->canUseProxySqlSidecar($proxySqlEnable, $mode);

        if (($this->isProxySqlSidecarActive === false) and ($this->isProxySqlServiceActive === false))
        {
            return;
        }

        $app = $this->app;

        $configs = $app['config']->get(self::DATABASE_CONFIG);

        $user = null;

        if(($this->app['config']->get(self::DATABASE_CONFIG . '.' . self::PROXY_SQL_ENABLE_PAYMENT_FETCH_REPLICA)) === true)
        {
            $this->proxyConnections = array_merge($this->proxyConnections,
                ['payment-fetch-replica-live','payment-fetch-replica-test']);
        }

        try
        {
            foreach ($configs as $connectionName => $config)
            {
                if ((in_array($connectionName, $this->proxyConnections, true) === true) and
                    (is_array($config) === true))
                {
                    if($this->isProxySqlServiceActive === true)
                    {
                        $proxySqlServiceConfig = $this->app['config']->get(self::DATABASE_CONFIG . '.' . self::PROXY_SQL_SERVICE_CONFIG);

                        $proxySqlServiceHost = $proxySqlServiceConfig['host'];

                        $proxySqlServicePort = $proxySqlServiceConfig['port'];

                        if (empty($config['read']) === false)
                        {
                            $user = $app['config']->get(self::DATABASE_CONFIG . '.' . $connectionName . '.read.username');

                            $this->originalConnectionConfig[$connectionName]['read'] = [
                                'host' => $app['config']->get(self::DATABASE_CONFIG . '.' . $connectionName . '.read.host'),
                                'port' => $app['config']->get(self::DATABASE_CONFIG . '.' . $connectionName . '.read.port'),
                            ];

                            $app['config']->set(self::DATABASE_CONFIG . '.' . $connectionName . '.read.host', $proxySqlServiceHost);
                            $app['config']->set(self::DATABASE_CONFIG . '.' . $connectionName . '.read.port', $proxySqlServicePort);
                        }

                        if (empty($config['write']) === false)
                        {
                            $user = $app['config']->get(self::DATABASE_CONFIG . '.' . $connectionName . '.write.username');

                            $this->originalConnectionConfig[$connectionName]['write'] = [
                                'host' => $app['config']->get(self::DATABASE_CONFIG . '.' . $connectionName . '.write.host'),
                                'port' => $app['config']->get(self::DATABASE_CONFIG . '.' . $connectionName . '.write.port'),
                            ];

                            $app['config']->set(self::DATABASE_CONFIG . '.' . $connectionName . '.write.host', $proxySqlServiceHost);
                            $app['config']->set(self::DATABASE_CONFIG . '.' . $connectionName . '.write.port', $proxySqlServicePort);
                        }

                        if (empty($config['host']) === false)
                        {
                            $this->originalConnectionConfig[$connectionName][''] = [
                                'host' => $app['config']->get(self::DATABASE_CONFIG . '.' . $connectionName . '.host'),
                            ];

                            $app['config']->set(self::DATABASE_CONFIG . '.' . $connectionName . '.host', $proxySqlServiceHost);
                        }

                        if (empty($config['port']) === false)
                        {
                            $this->originalConnectionConfig[$connectionName][''] = [
                                'port' => $app['config']->get(self::DATABASE_CONFIG . '.' . $connectionName . '.port'),
                            ];

                            $app['config']->set(self::DATABASE_CONFIG . '.' . $connectionName . '.port', $proxySqlServicePort);
                        }

                        if (empty($config['username']) === false)
                        {
                            $user = $config['username'];
                        }

                        $this->traceProxysqlConnection(self::PROXYSQL_SERVICE,  $proxySqlServiceConfig, $user);
                    }
                    else if($this->isProxySqlSidecarActive === true)
                    {
                        $proxySqlSocket = $this->app['config']->get(self::DATABASE_CONFIG . '.' . self::PROXY_SQL_SIDECAR_CONFIG);

                        // by default if port and unix_socket both are present. Laravel will prioritise socket over port
                        if (empty($config['read']) === false)
                        {
                            $user = $app['config']->get(self::DATABASE_CONFIG . '.' . $connectionName . '.read.username');
                            $app['config']->set(self::DATABASE_CONFIG . '.' . $connectionName . '.read.unix_socket', $proxySqlSocket);
                        }

                        if (empty($config['write']) === false)
                        {
                            $user = $app['config']->get(self::DATABASE_CONFIG . '.' . $connectionName . '.write.username');
                            $app['config']->set(self::DATABASE_CONFIG . '.' . $connectionName . '.write.unix_socket', $proxySqlSocket);
                        }

                        if (empty($config['host']) === false)
                        {
                            $app['config']->set(self::DATABASE_CONFIG . '.' . $connectionName . '.unix_socket', $proxySqlSocket);
                        }

                        if (empty($config['username']) === false)
                        {
                            $user = $config['username'];
                        }

                        $this->traceProxysqlConnection(self::PROXYSQL_SIDECAR, $proxySqlSocket, $user);
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

    public function resetDatabaseConnectionHostAndPort($name)
    {
        $app = $this->app;

        $connectionTypes = ['read', 'write', ''];

        foreach ($connectionTypes as $connectionType)
        {
            if (($app['config']->has(self::DATABASE_CONFIG . '.' . $name . '.' . $connectionType . '.host') === true) and
                (isset($this->originalConnectionConfig[$name][$connectionType]['host']) === true))
            {
                $app['config']->set(self::DATABASE_CONFIG . '.' . $name . '.' . $connectionType . '.host', $this->originalConnectionConfig[$name][$connectionType]['host']);
            }

            if (($app['config']->has(self::DATABASE_CONFIG . '.' . $name . '.' . $connectionType . '.port') === true) and
                (isset($this->originalConnectionConfig[$name][$connectionType]['port']) === true))
            {
                $app['config']->set(self::DATABASE_CONFIG . '.' . $name . '.' . $connectionType . '.port', $this->originalConnectionConfig[$name][$connectionType]['port']);
            }
        }
    }

    public function isProxySqlActive()
    {
        return (($this->isProxySqlSidecarActive === true) or ($this->isProxySqlServiceActive === true));
    }

    protected function canUseProxySqlSidecar($proxySqlEnable, $mode)
    {
        $proxySqlSocket = $this->app['config']->get(self::DATABASE_CONFIG . '.' . self::PROXY_SQL_SIDECAR_CONFIG);

        // cron env and workers will not have this file.
        if ((empty($proxySqlSocket) === true) or (file_exists($proxySqlSocket) === false))
        {
            return false;
        }

        // keeping enable value also on sidecar as current setup uses it for this.
        if (($proxySqlEnable === self::PROXYSQL_SIDECAR) or
            ($proxySqlEnable === self::ENABLE) or
            (($proxySqlEnable === self::TEST) and ($mode === Mode::TEST)))
        {
            return true;
        }

        return false;
    }

    protected function canUseProxySqlService($proxySqlEnable, $mode)
    {
        $proxySqlServiceConfig = $this->app['config']->get(self::DATABASE_CONFIG . '.' . self::PROXY_SQL_SERVICE_CONFIG);

        if ((empty($proxySqlServiceConfig) === true) or
            (empty($proxySqlServiceConfig['host']) === true) or
                (empty($proxySqlServiceConfig['port']) === true ))
        {
            return false;
        }

        if (($proxySqlEnable === self::PROXYSQL_SERVICE) or
            (($proxySqlEnable === self::TEST) and ($mode === Mode::TEST)))
        {
            return true;
        }

        return false;
    }

    protected function traceProxysqlConnection($type, $proxysqlConfig, $user) {
        //TODO: control logging from env variable as we might want to disable logging due to high volume.
        // commented out for now
        // enabling for api workers on proxySQL go-live, can be removed after this.
        $excludedWorkerLogs = ['worker:es_sync'];

        $runningInQueue = app()->runningInQueue();
        if ($runningInQueue === true) {
            $jobName = app('worker.ctx')->getJobName();

            if (in_array($jobName, $excludedWorkerLogs) === false) {
                $this->app['trace']->info(TraceCode::PROXY_SQL_CONNECTION, [
                    'type'              => $type,
                    'proxy_sql_config'  => $proxysqlConfig,
                    'user'              => $user,
                    'job_name'          => $jobName,
                ]);
            }
        }
    }
}
