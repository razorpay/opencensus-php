<?php

namespace Database;

use Config;

class DefaultConnection
{
    public static function set($mode)
    {
        if ($mode === 'test')
        {
            Config::set('database.default', 'test');
        }

        if ($mode === 'live')
        {
            Config::set('database.default', 'live');
        }
    }
}