<?php

namespace Database;

use Config;
use Constants\Mode;

class DefaultConnection
{
    public static function set($mode)
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