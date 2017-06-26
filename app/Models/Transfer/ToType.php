<?php

namespace RZP\Models\Transfer;

use Rzp\Exception;

class ToType
{
    const CUSTOMER      = 'customer';
    const ACCOUNT       = 'account';

    public static $allowedTypes = [
        self::CUSTOMER,
        self::ACCOUNT
    ];

    public static function validateDestination(string $method)
    {
        if (defined(__CLASS__ . '::' . strtoupper($method)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid Transfer destination: ' . $method);
        }
    }
}
