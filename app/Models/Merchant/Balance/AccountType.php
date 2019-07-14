<?php

namespace RZP\Models\Merchant\Balance;

class AccountType
{
    /**
     * Account types.
     */
    // These are Virtual Accounts
    const SHARED = 'shared';
    // These are Current Accounts
    const DIRECT = 'direct';

    public static function exists(string $type): bool
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }
}
