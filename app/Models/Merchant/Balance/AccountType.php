<?php

namespace RZP\Models\Merchant\Balance;

/**
 * Class AccountType
 *
 * ENUM, applicable to balance of type=banking
 *
 * @package RZP\Models\Merchant\Balance
 */
class AccountType
{
    /**
     * Direct accounts (ex: RBL current accounts)
     */
    const DIRECT = 'direct';

    /**
     * Shared network accounts
     */
    const SHARED = 'shared';

    public static function exists(string $accType): bool
    {
        $key = __CLASS__ . '::' . strtoupper($accType);

        return ((defined($key) === true) and (constant($key) === $accType));
    }
}
