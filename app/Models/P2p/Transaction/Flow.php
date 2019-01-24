<?php

namespace RZP\Models\P2p\Transaction;

class Flow
{
    const DEBIT     = 'debit';
    const CREDIT    = 'credit';

    public static function isValid(string $key): bool
    {
        return (defined(static::class.'::'.strtoupper($key)));
    }
}
