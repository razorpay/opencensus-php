<?php

namespace Database;

use Config;
use App;
use Http\Route;
use Constants\Mode;


class DefaultConnection
{
    public static function set($mode)
    {
        $currentRoute = self::getCurrentRoute();

        $slaveRoutes = self::getSlaveRoutes();

        if (in_array($currentRoute, $slaveRoutes) === true)
        {
            self::setSlaveDb($mode);
            return;
        }

        self::setMasterDb($mode);
    }


    public static function setSlaveDb($mode)
    {
        if ($mode === Mode::TEST)
        {
            Config::set('database.default', 'slave.test');
        }
        else if ($mode === Mode::LIVE)
        {
            Config::set('database.default', 'slave.live');
        }
    }


    public static function setMasterDb($mode)
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


    public static function getSlaveRoutes()
    {
        return Route::$slaveRoutes;
    }


    public static function getCurrentRoute()
    {
        $app = App::getFacadeRoot();
        return $app['router']->currentRouteName();
    }
}