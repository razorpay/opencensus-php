<?php

namespace RZP\Models\Payment;

use RZP\Exception\InvalidArgumentException;

class AuthType
{
    const NETBANKING    = 'netbanking';
    const AADHAAR       = 'aadhaar';

    public static $types = [
        self::NETBANKING,
        self::AADHAAR,
    ];

    public static function isAuthTypeValid($type): bool
    {
        return (in_array($type, self::$types, true) === true);
    }

    public static function validateAuthType($type)
    {
        if (self::isAuthTypeValid($type) === false)
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
