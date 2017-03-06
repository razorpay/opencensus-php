<?php

namespace RZP\Models\Transfer;

class ToType
{
    const CUSTOMER      = 'customer';
    const ACCOUNT       = 'account';

    public static $allowedTypes = [
        self::CUSTOMER,
        self::ACCOUNT
    ];
}
