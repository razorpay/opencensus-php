<?php

namespace RZP\Gateway\Wallet\Freecharge;

use RZP\Error;
use RZP\Error\ErrorCode;

class ResponseCodeMap
{
    public static $codes = array(
        'E702'  => ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
        'E701'  => ErrorCode::BAD_REQUEST_PAYMENT_OTP_EXPIRED,
    );

    protected static $success = array(
        0
    );

    public static function getResponseMessage($code)
    {
        $codes = self::$codes;

        return $codes[(int)$code];
    }

    public static function getStatus($code)
    {
        ; // @todo
    }

    public static function getApiErrorCode($code)
    {
        $class = 'RZP\Error\ErrorCode::';

        if (empty($code) or
            isset(self::$codes[$code]) === false)
        {
            return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
        }

        $apiCode = self::$codes[$code];

        if (defined($class . $apiCode))
        {
            return $apiCode;
        }

        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }
}
