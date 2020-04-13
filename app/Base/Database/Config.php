<?php

namespace RZP\Base\Database;

use App;

use RZP\Constants\Mode;
use RZP\Http\Route;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Trace\TraceCode;

class Config
{
    const PROXY_SQL_FEATURE     = 'proxy_sql';
    const TEST_ROUTE            = 'api_status';

    const RAZORX_VALUE          = 'on';

    const DATABASE_CONFIG       = 'database.connections';

    const PROXY_SQL_CONFIG      = 'proxy_sql_unix_socket';

    const IS_WORKER_POD         = 'is_worker_pod';

    const WORKER_CONFIG         = 'worker';

    const PROXY_CONNECTIONS     = [
        'live',
        'test',
        'slave-test',
        'slave-live',
    ];

    public function setDatabaseHostsIfApplicable()
    {
        $app = App::getFacadeRoot();

        $proxySqlSocket = $app['config']->get(self::DATABASE_CONFIG . '.' . self::PROXY_SQL_CONFIG);

        if ((empty($proxySqlSocket) === true) or (file_exists($proxySqlSocket) === false))
        {
            return;
        }

        // Worker only makes a db connection once. So we will not need proxySQL for this.
        $isWorkerPod = $app['config']->get(self::WORKER_CONFIG . '.' . self::IS_WORKER_POD);

        if (empty($isWorkerPod) === true)
        {
            return;
        }

        // mode is set inside request context, from Throttle Middleware
        // mode is not present for callbacks and workers.
        $mode = $app['request.ctx']->getMode() ?: Mode::LIVE;

        $currentRoute = $app['request.ctx']->getRoute();

        $allowedRoutes = array_merge(Route::$admin, Route::$proxy, [self::TEST_ROUTE]);

        if ((in_array($currentRoute, $allowedRoutes, true) === false))
        {
            return;
        }

        $value = $app['razorx']->getTreatment(UniqueIdEntity::generateUniqueId(), self::PROXY_SQL_FEATURE, $mode);

        // always allow for test route. so that we can test before activating razorX experiment.
        if (($currentRoute !== self::TEST_ROUTE) and ($value !== self::RAZORX_VALUE))
        {
            return;
        }

        $configs = $app['config']->get(self::DATABASE_CONFIG);

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
        $app = App::getFacadeRoot();

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
}

