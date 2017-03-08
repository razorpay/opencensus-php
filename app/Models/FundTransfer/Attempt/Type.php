<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Exception;

class Type
{
    const SETTLEMENT = 'settlement';

    public static function validateType(string $type)
    {
        if (defined(__CLASS__.'::'.strtoupper($type)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid FundTransferAttempt type: ' . $type);
        }
    }

    public static function getEntityClass(string $type)
    {
        return 'RZP\\Models\\' . ucfirst($type) . '\Entity';
    }
}