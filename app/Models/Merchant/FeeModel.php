<?php

namespace RZP\Models\Merchant;

class FeeModel
{
    const PREPAID   = 'prepaid';
    const POSTPAID  = 'postpaid';

    protected static $values = [
        self::PREPAID   => 0,
        self::POSTPAID  => 1,
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
