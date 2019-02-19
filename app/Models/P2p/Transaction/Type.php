<?php

namespace RZP\Models\P2p\Transaction;

class Type
{
    const PAY       = 'pay';
    const COLLECT   = 'collect';

    public static function isValid(string $key): bool
    {
        return (defined(static::class.'::'.strtoupper($key)));
    }
}
