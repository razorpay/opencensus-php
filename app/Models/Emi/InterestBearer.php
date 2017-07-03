<?php

namespace RZP\Models\Emi;

class InterestBearer
{
    const MERCHANT = 'merchant';
    const CUSTOMER = 'customer';

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
