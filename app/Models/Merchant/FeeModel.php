<?php

namespace RZP\Models\Merchant;

class FeeModel
{
    const PREPAID   = 'prepaid';
    const POSTPAID  = 'postpaid';
    const NA        = 'na';

    protected static $values = [
        self::PREPAID   => 0,
        self::POSTPAID  => 1,
        self::NA        => 2,
    ];

    public static function getValueForFeeModelString($feeModel)
    {
        return self::$values[$feeModel];
    }

    public static function getFeeModelStringForValue($feeModelValue)
    {
        $values = array_flip(self::$values);

        return $values[$feeModelValue];
    }
}
