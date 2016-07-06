<?php

namespace Database;

use Config;
use App;
use RZP\Http\Route;
use RZP\Constants\Mode;

class DefaultConnection
{
    public static function set($mode)
    {
        $currentRoute = Route::getCurrentRouteName();

        $slaveRoutes = Route::getSlaveRoutes();

        if (in_array($currentRoute, $slaveRoutes) === true)
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
