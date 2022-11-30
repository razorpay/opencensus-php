<?php

namespace Database;

use App;
use Config;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use Razorpay\Trace\Logger as Trace;

class DefaultConnection
{
    const SLAVE  = 'slave';
    const MASTER = 'master';

    public static function set($mode)
    {
        self::setMasterConnection($mode);
    }

    public static function setSlaveConnection($mode)
    {
        if ($mode === Mode::TEST)
        {
            Config::set('database.default', Connection::SLAVE_TEST);
        }
        else if ($mode === Mode::LIVE)
        {
            $connection = self::getLiveConnectionName(self::SLAVE);

            Config::set('database.default', $connection);
        }
    }

    public static function setMasterConnection($mode)
    {
        if ($mode === Mode::TEST)
        {
            Config::set('database.default', Connection::TEST);
        }
        else if ($mode === Mode::LIVE)
        {
            $connection = self::getLiveConnectionName(self::MASTER);

            Config::set('database.default', $connection);
        }
    }

    private static function getLiveConnectionName(string $connectionType = self::MASTER): string
    {
        $connection = ($connectionType === self::SLAVE) ? Connection::SLAVE_LIVE : Connection::LIVE;

        try
        {
            $app = App::getFacadeRoot();

            $instanceType = getenv('INSTANCE_TYPE');
            $isWorkerPod  = getenv('IS_WORKER_POD');
            $type  = 'payments';

            if (((isset($app['api.route']) === true) and ($app['api.route']->isRazorpayXRoute() === true)) or
                ((isset($app['worker.ctx']) === true) and ($app['worker.ctx']->isRazorpayXJob() === true)))
            {
                $type  = 'razorpayx';
            }

            $app['trace']->count(Metric::DB_CONNECTION_CLASSIFICATION, [
                'classification'  => $type,
                'worker_pod'      => $isWorkerPod,
                'instance_type'   => $instanceType,
                'connection_type' => $connectionType,
            ]);
        }
        catch (\Throwable $exception)
        {
            App::getFacadeRoot()['trace']->traceException(
                $exception,
                Trace::WARNING,
                TraceCode::DB_CONNECTION_CLASSIFICATION_EXCEPTION);
        }

        return $connection;
    }
}
