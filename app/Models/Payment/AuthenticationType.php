<?php

namespace RZP\Models\Payment;

use RZP\Exception\InvalidArgumentException;

class AuthenticationType
{
    const NETBANKING    = 'netbanking';
    const AADHAAR       = 'aadhaar';
    // const DEBIT_CARD    = 'debit_card';

    public static function isAuthenticationTypeValid($type)
    {
        return (defined(__CLASS__ . '::' . strtoupper($type)));
    }

    public static function validateAuthenticationType($type)
    {
        if (self::isAuthenticationTypeValid($type) === false)
        {
            throw new InvalidArgumentException(
                'Invalid authentication type',
                [
                    'field'                 => Entity::AUTHENTICATION_TYPE,
                    'authentication_type'   => $type
                ]);
        }
    }
}
