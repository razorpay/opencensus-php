<?php

namespace RZP\Models\P2p\Mandate;

/**
 * Class Type
 *
 * @package RZP\Models\P2p\Mandate
 */
class Type
{
    const COLLECT   = 'collect';

    /**
     * Checks if provided type is valid
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
