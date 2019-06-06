<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Models\BankingAccount;

/**
 * Class AccountProvider
 *
 * ENUM, applicable to balance of type=banking and account_type=direct
 *
 * @package RZP\Models\Merchant\Balance
 */
class AccountProvider
{
    const RBL = BankingAccount\Channel::RBL;

    public static function exists(string $accProvider): bool
    {
        $key = __CLASS__ . '::' . strtoupper($accProvider);

        return ((defined($key) === true) and (constant($key) === $accProvider));
    }
}
