<?php

namespace RZP\Models\Payment;

use RZP\Exception;
use RZP\Models\Feature;

class AuthType
{
    const NETBANKING    = 'netbanking';
    const AADHAAR       = 'aadhaar';
    const PIN           = 'pin';
    const _3DS          = '3ds';

    public static $types = [
        Method::EMANDATE => [
            self::NETBANKING,
            self::AADHAAR,
        ],
        Method::CARD    => [
            self::PIN,
            self::_3DS
        ],
    ];

    public static $featureToAuthMap = [
        self::PIN => Feature\Constant::ATM_PIN_AUTH,
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
            throw new Exception\InvalidArgumentException(
                'Invalid auth type',
                [
                    'field'                 => Entity::AUTH_TYPE,
                    'auth_type'             => $type
                ]);
        }
    }

    public static function validateFeatureBasedAuth($merchant, $type)
    {
        if (isset(self::$featureToAuthMap[$type]) === true)
        {
            if ($merchant->isFeatureEnabled(self::$featureToAuthMap[$type]) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The auth_type field is invalid');
            }
        }
    }
}
