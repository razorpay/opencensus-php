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
