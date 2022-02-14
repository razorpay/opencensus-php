<?php

namespace RZP\Models\BankingAccountStatement\Details;

use RZP\Exception;

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

    public static function getAccountTypes()
    {
        return [
            self::DIRECT,
            self::SHARED,
        ];
    }

    public static function validate(string $accountType = null)
    {
        if (in_array($accountType, self::getAccountTypes(), true) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid account_type: ' . $accountType);
        }
    }
}
