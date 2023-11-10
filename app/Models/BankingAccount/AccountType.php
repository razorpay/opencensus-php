<?php

namespace RZP\Models\BankingAccount;

class AccountType
{
    const NODAL     = 'nodal';
    const SAVINGS   = 'savings';
    const CURRENT   = 'current';
    const DIRECT    = 'direct';
    const CORP_CARD = "corp_card";

    // Todo:: RX Wallet Payout Use Case: Add rx_wallet account type for banking account

    public static function isValid(string $type): bool
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }
}
