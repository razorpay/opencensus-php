<?php

namespace RZP\Models\P2p\Mandate;

/**
 * Class Status
 *
 * @package RZP\Models\P2p\Mandate
 */
class Status
{
    const CREATED    = 'created';
    const COMPLETED  = 'completed';
    const FAILED     = 'failed';
    const APPROVED   = 'approved';
    const PAUSED     = 'paused';
    const REVOKED    = 'revoked';

    // Internal Status
    const REQUESTED  = 'requested';
    const PENDING    = 'pending';
    const INITIATED  = 'initiated';
    const EXPIRED    = 'expired';
    const REJECTED   = 'rejected';

    public static function isValid(string $key): bool
    {
        return (defined(static::class.'::'.strtoupper($key)) === true);
    }
}
