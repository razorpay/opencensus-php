<?php

namespace RZP\Models\Merchant;

class Plan
{
    const PREPAID   = 'prepaid';
    const POSTPAID  = 'postpaid';

    protected static $values = [
        self::PREPAID   => 0,
        self::POSTPAID  => 1,
    ];

    public static function getValueForPlanString($plan)
    {
        return self::$values[$plan];
    }

    public static function getPlanStringForValue($planValue)
    {
        $values = array_flip(self::$values);

        return $values[$planValue];
    }
}
