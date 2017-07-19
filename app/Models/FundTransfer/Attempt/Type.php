<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Exception;
use RZP\Constants\Entity;

class Type
{
    const SETTLEMENT = Entity::SETTLEMENT;
    const PAYOUT     = Entity::PAYOUT;

    protected static $validTypes = [self::SETTLEMENT, self::PAYOUT];

    public static function validateType(string $type)
    {
        if (in_array($type, self::$validTypes, true) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid FundTransferAttempt type: ' . $type);
        }
    }
}