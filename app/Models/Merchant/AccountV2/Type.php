<?php

namespace RZP\Models\Merchant\AccountV2;

class Type
{
    const STANDARD          = 'standard';
    const ROUTE             = 'route';

    public static $allowedTypes = [
        self::ROUTE,
        self::STANDARD,
    ];
}
