<?php

namespace RZP\Models\BankingAccount;

class AccountType
{
    const NODAL   = 'nodal';
    const SAVINGS = 'savings';
    const CURRENT = 'current';
    const DIRECT  = 'direct';

    public static function isValid(string $type): bool
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }
}
