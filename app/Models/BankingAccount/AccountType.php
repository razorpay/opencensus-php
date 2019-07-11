<?php

namespace RZP\Models\BankingAccount;

class AccountType
{
    const VIRTUAL       = 'virtual';
    const CURRENT       = 'current';

    public static function isAccountTypeValid($type)
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }
}
