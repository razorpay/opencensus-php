<?php

namespace RZP\Models\Payment;

use RZP\Models\Feature;
use RZP\Exception\BadRequestValidationFailureException;

class AuthType
{
    const NETBANKING   = 'netbanking';
    const DEBITCARD    = 'debitcard';
    const AADHAAR      = 'aadhaar';
    const AADHAAR_FP   = 'aadhaar_fp';
    const SKIP         = 'skip';
    const PIN          = 'pin';
    const _3DS         = '3ds';
    const OTP          = 'otp';
    const HEADLESS_OTP = 'headless_otp';
    const IVR          = 'ivr';
    const UNKNOWN      = 'unknown';
    const PHYSICAL     = 'physical';
    const MIGRATED     = 'migrated';

    public static $types = [
        Method::EMANDATE => [
            self::NETBANKING,
            self::AADHAAR,
            //self::AADHAAR_FP,
            self::DEBITCARD,
            self::MIGRATED,
        ],
        Method::NACH    => [
            self::PHYSICAL,
            self::MIGRATED,
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

    const DEFAULT_AUTH_ORDER = [
        Method::CARD => [
            self::UNKNOWN => [
                self::PIN,
                self::OTP,
                self::IVR,
                self::HEADLESS_OTP,
                self::_3DS,
            ],
            self::OTP => [
                self::IVR,
                self::OTP,
                self::HEADLESS_OTP,
            ],
            self::PIN => [
                self::PIN,
            ],
            self::SKIP => [
                self::SKIP,
            ],
            self::_3DS => [
                self::_3DS,
            ],
        ],
        Method::EMI => [
            self::UNKNOWN => [
                self::OTP,
                self::IVR,
                self::HEADLESS_OTP,
                self::_3DS,
            ],
            self::OTP => [
                self::IVR,
                self::OTP,
                self::HEADLESS_OTP,
            ],
            self::_3DS => [
                self::_3DS,
            ],
        ],
    ];

    // auth types which will get set if payment create request has auth_type as OTP
    public static $otpAuthTypes = [
        self::HEADLESS_OTP,
        self::IVR,
    ];

    public static $featureToAuthMap = [
        self::PIN  => [Feature\Constants::ATM_PIN_AUTH],
        self::OTP  => [Feature\Constants::AXIS_EXPRESS_PAY,],
        self::SKIP => [Feature\Constants::DIRECT_DEBIT],
    ];

    //auth which can fallback to 3ds
    public static $redirectTo3dsAuth = [
        self::OTP,
        self::HEADLESS_OTP,
        self::IVR,
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


    public static function isOtpAuth($authType): bool
    {
        if (in_array($authType, self::DEFAULT_AUTH_ORDER[Method::CARD][self::OTP], true) === true)
        {
            return true;
        }

        return false;
    }

    public static function validateFeatureBasedAuth($merchant, $type)
    {
        if (isset(self::$featureToAuthMap[$type]) === true)
        {
            $enabled = false;

            foreach (self::$featureToAuthMap[$type] as $feature)
            {
                $enabled = ($merchant->isFeatureEnabled($feature) or $enabled);
            }

            if ($type === self::OTP)
            {
                $enabled = ($merchant->isHeadlessEnabled() or $enabled);
            }

            if ($type === self::OTP)
            {
                $enabled = ($merchant->isIvrEnabled() or $enabled);
            }

            if ($enabled === false)
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
            $enabled = false;

            foreach (self::$featureToAuthMap[$type] as $feature)
            {
                $enabled = ($merchant->isFeatureEnabled($feature) or $enabled);
            }

            if ($type === self::OTP)
            {
                $enabled = ($merchant->isHeadlessEnabled() or $enabled);
            }

            if ($type === self::OTP)
            {
                $enabled = ($merchant->isIvrEnabled() or $enabled);
            }

            return $enabled;
        }

        return true;
    }

    public static function isRedirectTo3dsAuth(string $auth)
    {
        if (in_array($auth, self::$redirectTo3dsAuth, true) === true)
        {
            return true;
        }

        return false;
    }
}
