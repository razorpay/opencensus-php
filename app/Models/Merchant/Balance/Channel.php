<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Models\BankingAccount;

/**
 * Class Channel
 *
 * ENUM, applicable to balance of type=banking and account_type=direct
 *
 * @package RZP\Models\Merchant\Balance
 */
class Channel
{
    const RBL     = BankingAccount\Channel::RBL;
    const ICICI   = BankingAccount\Channel::ICICI;
    const AXIS    = BankingAccount\Channel::AXIS;
    const YESBANK = BankingAccount\Channel::YESBANK;

    public static function exists(string $channel): bool
    {
        $key = __CLASS__ . '::' . strtoupper($channel);

        return ((defined($key) === true) and (constant($key) === $channel));
    }
}
