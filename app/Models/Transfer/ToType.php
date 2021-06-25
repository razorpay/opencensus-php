<?php

namespace RZP\Models\Transfer;

use RZP\Exception;

class ToType
{
    const CUSTOMER      = 'customer';
    const ACCOUNT       = 'account';
    const BALANCE       = 'balance';

    public static $allowedTypes = [
        self::CUSTOMER,
        self::BALANCE,
        self::ACCOUNT
    ];

    public static function validateDestination(string $method)
    {
        if (defined(__CLASS__ . '::' . strtoupper($method)) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid Transfer destination: ' . $method);
        }
    }
}
