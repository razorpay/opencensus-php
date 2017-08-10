<?php

namespace RZP\Models\Admin;

class ConfigKey
{
    const TERMINAL_SELECTION_LOG_VERBOSE = 'terminal_selection_log_verbose';

    const PUBLIC_KEYS = [
        self::TERMINAL_SELECTION_LOG_VERBOSE,
    ];

    public static function isSensitive(string $key)
    {
        if (in_array($key, self::PUBLIC_KEYS, true) === true)
        {
            return false;
        }

        return true;
    }
}
