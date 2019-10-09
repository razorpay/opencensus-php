<?php

namespace RZP\Models\TerminalOnboardingDetail;

class Status
{
    const CREATED           = 'created';

    const QUEUED            = 'queued';

    const PENDING           = 'pending';

    const ACTIVATED         = 'activated';

    const FAILED            = 'failed';

    const ACTIVATION_FAILED = 'activation_failed';

    public static function exists(string $status): bool
    {
        return (defined(self::class . '::' . strtoupper($status)));
    }
}
