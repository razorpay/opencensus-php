<?php

namespace RZP\Models\Payment;

use RZP\Exception\InvalidArgumentException;

class AuthType
{
    const NETBANKING    = 'netbanking';
    const AADHAAR       = 'aadhaar';

    // @todo: Map it on the basis of method
    public static $types = [
        self::NETBANKING,
        self::AADHAAR,
    ];

    // @todo: Check validity on the basis of method
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
