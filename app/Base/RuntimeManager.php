<?php

namespace RZP\Base;

use App;

class RuntimeManager
{
    const MEMORY_LIMIT  = 'memory_limit';
    const MAX_EXEC_TIME = 'max_execution_time';

    public static function setMemoryLimit($limit)
    {
        if (App::environment('testing') === false)
        {
            ini_set(static::MEMORY_LIMIT, $limit);
        }
    }

    public static function setTimeLimit($time)
    {
        if (App::environment('testing') === false)
        {
            set_time_limit($time);
        }
    }

    public static function setMaxExecTime($time)
    {
        if (App::environment('testing') === false)
        {
            ini_set(static::MAX_EXEC_TIME, $time);
        }
    }
}
