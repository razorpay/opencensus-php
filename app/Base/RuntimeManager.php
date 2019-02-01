<?php

namespace RZP\Base;

use App;

class RuntimeManager
{
    const MEMORY_LIMIT  = 'memory_limit';
    const MAX_EXEC_TIME = 'max_execution_time';

    /**
     * @param string $limit - E.g. 256M.
     */
    public static function setMemoryLimit(string $limit)
    {
        if (App::environment('testing') === false)
        {
            ini_set(static::MEMORY_LIMIT, $limit);
        }
    }

    public static function setTimeLimit(int $secs)
    {
        if (App::environment('testing') === false)
        {
            set_time_limit($secs);
        }
    }

    public static function setMaxExecTime(int $secs)
    {
        if (App::environment('testing') === false)
        {
            ini_set(static::MAX_EXEC_TIME, $secs);
        }
    }
}
