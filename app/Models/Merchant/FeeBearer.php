<?php

namespace RZP\Models\Merchant;

class FeeBearer
{
    const PLATFORM = 'platform';
    const CUSTOMER = 'customer';

    // const PLATFORM = 0;
    // const CUSTOMER = 1;

    protected static $values = [
        self::PLATFORM => 0,
        self::CUSTOMER => 1,
    ];

    public static function getValueForBearerString($bearer)
    {
        return self::$values[$bearer];
    }

    public static function getBearerStringForValue($bearerValue)
    {
        $values = array_flip(self::$values);

        return $values[$bearerValue];
    }
}
