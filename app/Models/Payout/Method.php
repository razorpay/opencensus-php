<?php

namespace RZP\Models\Payout;

use RZP\Exception;
use RZP\Constants;

class Method
{
    const FUND_TRANSFER     = 'fund_transfer';
    const UPI               = 'upi';

    public static function validateMethod($method)
    {
        if (defined(__CLASS__ . '::' . strtoupper($method)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid Payout method: ' . $method);
        }
    }
}
