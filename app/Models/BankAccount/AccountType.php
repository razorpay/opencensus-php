<?php

namespace RZP\Models\BankAccount;

class AccountType
{
    const SAVINGS       = 'savings';
    const CURRENT       = 'current';

    public static function isAccountTypeValid($type)
    {
        return (defined(__CLASS__ . '::' . strtoupper($type)));
    }
}
