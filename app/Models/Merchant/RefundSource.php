<?php

namespace RZP\Models\Merchant;

class RefundSource
{
    const BALANCE   = 'balance';
    const CREDITS   = 'credits';
    const NA        = 'na';

    protected static $values = [
        self::NA        => -1,
        self::BALANCE   => 0,
        self::CREDITS   => 1,
    ];

    public static function getValueForRefundSourceString($feeModel)
    {
        return self::$values[$feeModel];
    }

    public static function getRefundSourceStringForValue($feeModelValue)
    {
        $values = array_flip(self::$values);

        return $values[$feeModelValue];
    }
}
