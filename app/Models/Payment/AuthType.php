<?php

namespace RZP\Models\Payment;

use RZP\Models\Feature;
use RZP\Exception\BadRequestValidationFailureException;

class AuthType
{
    const NETBANKING    = 'netbanking';
    const AADHAAR       = 'aadhaar';
    const SKIP          = 'skip';
    const PIN           = 'pin';
    const _3DS          = '3ds';
    const OTP           = 'otp';

    public static $types = [
        Method::EMANDATE => [
            self::NETBANKING,
            self::AADHAAR,
        ],
        Method::CARD    => [
            self::PIN,
            self::_3DS,
            self::OTP,
            self::SKIP,
        ],
        Method::EMI     => [
            self::PIN,
            self::_3DS,
            self::OTP,
        ],
    ];

    public static $featureToAuthMap = [
        self::PIN => Feature\Constants::ATM_PIN_AUTH,
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
            throw new BadRequestValidationFailureException(
                'The selected auth_type is invalid',
                Entity::AUTH_TYPE,
                [
                    Entity::AUTH_TYPE => $type,
                    Entity::METHOD    => $method,
                ]);
        }
    }

    public static function getAuthTypeForMethod($method)
    {
        return self::$types[$method];
    }

    public static function validateFeatureBasedAuth($merchant, $type)
    {
        if (isset(self::$featureToAuthMap[$type]) === true)
        {
            if ($merchant->isFeatureEnabled(self::$featureToAuthMap[$type]) === false)
            {
                throw new BadRequestValidationFailureException(
                    'The selected auth_type is invalid',
                    Entity::AUTH_TYPE,
                    [
                        Entity::AUTH_TYPE => $type,
                    ]);
            }
        }
    }

    public static function isFeatureBasedAuthEnabled($merchant, $type)
    {
        if (isset(self::$featureToAuthMap[$type]) === true)
        {
            return $merchant->isFeatureEnabled(self::$featureToAuthMap[$type]);
        }

        return true;
    }
}
