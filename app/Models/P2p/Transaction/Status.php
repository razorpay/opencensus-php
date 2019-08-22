<?php

namespace RZP\Models\P2p\Transaction;

class Status
{
    const CREATED    = 'created';
    const COMPLETED  = 'completed';
    const FAILED     = 'failed';

    // Internal Status
    const REQUESTED  = 'requested';
    const PENDING    = 'pending';
    const INITIATED  = 'initiated';
    const EXPIRED    = 'expired';
    const REJECTED   = 'rejected';

    public static function isValid(string $key): bool
    {
        return (defined(static::class.'::'.strtoupper($key)));
    }
}
