<?php

namespace RZP\Models\Payout;

use RZP\Exception;
use RZP\Constants\Entity as E;

class Method
{
    const FUND_TRANSFER     = 'fund_transfer';
    const UPI               = 'upi';

    public static $destinationMethodMap = [
        E::BANK_ACCOUNT => self::FUND_TRANSFER,
        E::VPA          => self::UPI,
    ];

    public static function validateMethod($method)
    {
        if (defined(__CLASS__ . '::' . strtoupper($method)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid Payout method: ' . $method);
        }
    }
}
