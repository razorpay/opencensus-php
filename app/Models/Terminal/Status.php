<?php

namespace RZP\Models\Terminal;

class Status
{
    const CREATED           = 'created';

    const PENDING           = 'pending';

    const ACTIVATED         = 'activated';

    const FAILED            = 'failed';

    public static function exists(string $status): bool
    {
        return (defined(self::class . '::' . strtoupper($status)));
    }
}
