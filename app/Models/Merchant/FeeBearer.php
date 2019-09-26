<?php

namespace RZP\Models\Merchant;

class FeeBearer
{
    const PLATFORM = 'platform';
    const CUSTOMER = 'customer';
    const NA       = 'na';

    protected static $values = [
        self::NA       => -1,
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
