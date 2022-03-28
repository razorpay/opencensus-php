<?php

namespace RZP\Models\Merchant;

class FeeBearer
{
    const PLATFORM = 'platform';
    const CUSTOMER = 'customer';
    const DYNAMIC  = 'dynamic';
    const NA       = 'na';
    const MERCHANT = 'merchant'; //aka platform
    const PAYER = 'payer'; //aka customer

    const FEE_BEARER_TYPE_MAP = [
        'merchant' => self::PLATFORM,
        'payer' => self::CUSTOMER,
    ];

    protected static $values = [
        self::NA       => -1,
        self::PLATFORM => 0,
        self::CUSTOMER => 1,
        self::DYNAMIC  => 2,
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
