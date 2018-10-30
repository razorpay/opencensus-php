<?php

namespace RZP\Models\BankAccount;

class AccountType
{
    const SAVINGS       = 'savings';
    const CURRENT       = 'current';

    public static function isAccountTypeValid($type)
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }
}
