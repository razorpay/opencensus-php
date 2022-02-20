<?php

namespace RZP\Models\P2p\Mandate;

/**
 * Class Flow
 *
 * @package RZP\Models\P2p\Mandate
 */
class Flow
{
    const DEBIT     = 'debit';

    /**
     * Checks if provided flow is valid
     *
     * @param string $key
     *
     * @return bool
     */
    public static function isValid(string $key): bool
    {
        return (defined(static::class.'::'.strtoupper($key)) === true);
    }
}
