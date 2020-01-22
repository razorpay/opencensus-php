<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Exception;
use RZP\Constants;

class Type
{
    const SETTLEMENT              = Constants\Entity::SETTLEMENT;
    const PAYOUT                  = Constants\Entity::PAYOUT;
    const REFUND                  = Constants\Entity::REFUND;
    const FUND_ACCOUNT_VALIDATION = Constants\Entity::FUND_ACCOUNT_VALIDATION;

    // Request Types to the bank
    // based on these type interaction with nodal account may differ
    const PRIMARY       = 'primary';
    const BANKING       = 'banking';
    const SYNC          = 'sync';

    protected static $validTypes = [
        self::FUND_ACCOUNT_VALIDATION,
        self::SETTLEMENT,
        self::PAYOUT,
        self::REFUND,
    ];

    protected static $notifyTypes = [
        self::SETTLEMENT,
    ];

    public static function validateType(string $type)
    {
        if (in_array($type, self::$validTypes, true) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid FundTransferAttempt type: ' . $type);
        }
    }

    public static function isNotifyType(string $type): bool
    {
        return (in_array($type, self::$notifyTypes, true) === true);
    }
}
