<?php

namespace RZP\Models\Payment;

use RZP\Exception\InvalidArgumentException;

class AuthType
{
    const NETBANKING    = 'netbanking';
    const AADHAAR       = 'aadhaar';
    const DEBIT_PIN     = 'debit_pin';

    public static $types = [
        Method::EMANDATE => [
            self::NETBANKING,
            self::AADHAAR,
        ],
        Method::CARD    => [
            self::DEBIT_PIN,
        ],
    ];

    public static function isAuthTypeValid($type, $method): bool
    {
        if (isset(self::$types[$method]) === false)
        {
            return false;
        }

        return (in_array($type, self::$types[$method], true) === true);
    }

    public static function validateAuthType($type, $method)
    {
        if (self::isAuthTypeValid($type, $method) === false)
        {
            throw new InvalidArgumentException(
                'Invalid auth type',
                [
                    'field'                 => Entity::AUTH_TYPE,
                    'auth_type'             => $type
                ]);
        }
    }
}
