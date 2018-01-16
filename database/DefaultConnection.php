<?php

namespace Database;

use App;
use Route;
use Config;

use RZP\Constants\Mode;
use RZP\Models\Admin\ConfigKey;

class DefaultConnection
{
    public static function set($mode)
    {
        $currentRoute = Route::currentRouteName();

        // adding namespace above causes conflicts on Route class
        $slaveRoutes = \RZP\Http\Route::getSlaveRoutes();

        //
        // In the testing environment, we can't set slave connection because all
        // entities created during test execution are not committed and we can't
        // fetch them using a different slave connection
        //
        if ((\App::getFacadeRoot()['env'] !== 'testing') and
            (in_array($currentRoute, $slaveRoutes) === true))
        {
            $skipSlave = self::getSkipSlaveConfig();

            if ($skipSlave === false)
            {
                self::setSlaveConnection($mode);

                return;
            }
        }

        self::setMasterConnection($mode);
    }

    public static function getSkipSlaveConfig()
    {
        $app = \App::getFacadeRoot();

        $cache = $app['cache'];

        $trace = $app['trace'];

        $skipSlave = false;

        try
        {
            if (isset($cache) === true)
            {
                $skipSlave = boolval($cache->get(ConfigKey::SKIP_SLAVE));
            }
        }
        catch (\Throwable $ex)
        {
            $trace->traceException($ex);
        }

        return $skipSlave;
    }

    public static function setSlaveConnection($mode)
    {
        if ($mode === Mode::TEST)
        {
            Config::set('database.default', 'slave-test');
        }
        else if ($mode === Mode::LIVE)
        {
            Config::set('database.default', 'slave-live');
        }
    }

    public static function setMasterConnection($mode)
    {
        if ($mode === Mode::TEST)
        {
            Config::set('database.default', 'test');
        }
        else if ($mode === Mode::LIVE)
        {
            Config::set('database.default', 'live');
        }
    }
}
