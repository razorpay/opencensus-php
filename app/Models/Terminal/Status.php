<?php

namespace RZP\Models\Terminal;

class Status
{
    const CREATED           = 'created';

    const PENDING           = 'pending';

    const ACTIVATED         = 'activated';

    const DEACTIVATED       = 'deactivated';

    const FAILED            = 'failed';

    const POSSIBLE_STATUS_FOR_WORLDLINE_ENABLED_TERMINAL = [
        self::PENDING,
        self::ACTIVATED
    ];

    public static function exists(string $status): bool
    {
        return (defined(self::class . '::' . strtoupper($status)));
    }
}
