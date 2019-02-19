<?php

namespace RZP\Models\FundTransfer\Attempt;

class Purpose
{
    const REFUND        = 'refund';
    const SETTLEMENT    = 'settlement';
    const PENNY_TESTING = 'penny_testing';

    const TYPE_LIST = [
        self::REFUND,
        self::SETTLEMENT,
        self::PENNY_TESTING,
    ];

    public static function isValid(string $type)
    {
        return (in_array($type, self::TYPE_LIST, true) === true);
    }
}
