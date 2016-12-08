<?php

namespace RZP\Models\Transfer;

class ToType
{
    const CUSTOMER      = 'customer';
    const VENDOR        = 'vendor';

    public static $allowedTypes = [
        self::CUSTOMER,
        self::VENDOR
    ];
}
