<?php

namespace Database;

use Config;
use App;
use Route;
use RZP\Constants\Mode;

class DefaultConnection
{
    public static function set($mode)
    {
        $currentRoute = Route::currentRouteName();

        // adding namespace above causes conflicts on Route class
        $slaveRoutes = \RZP\Http\Route::getSlaveRoutes();

        // In the testing environment, we can't set slave connection because all
        // entities created during test execution are not committed and we can't
        // fetch them using a different slave connection
        if ((\App::getFacadeRoot()['env'] !== 'testing') and
            (in_array($currentRoute, $slaveRoutes) === true))
        {
            self::setSlaveConnection($mode);
            return;
        }

        self::setMasterConnection($mode);
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
