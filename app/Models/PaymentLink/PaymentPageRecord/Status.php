<?php

namespace RZP\Models\PaymentLink\PaymentPageRecord;

use RZP\Exception;

class Status
{
    const PAID       = 'paid';
    const UNPAID     = 'unpaid';
    const REFUNDED   = 'refunded';

    public static function isStatusValid($status) : bool
    {
        $key = __CLASS__ . '::' . strtoupper($status);

        return ((defined($key) === true) and (constant($key) === $status));
    }

    public static function validateStatus(string $status)
    {
        if (self::isStatusValid($status) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Not a valid status: ' . $status);
        }
    }

}
