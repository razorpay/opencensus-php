<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Exception\BadRequestValidationFailureException;

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

    /**
     * Corp card accounts
     */
    const CORP_CARD = 'corp_card';

    public static function exists(string $accType): bool
    {
        $key = __CLASS__ . '::' . strtoupper($accType);

        return ((defined($key) === true) and (constant($key) === $accType));
    }

    public static function validate(string $accountType)
    {
        if (self::exists($accountType) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid payout account type for balance: ' . $accountType);
        }
    }
}
