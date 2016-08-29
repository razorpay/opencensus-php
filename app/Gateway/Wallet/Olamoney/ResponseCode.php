<?php

namespace RZP\Gateway\Wallet\Olamoney;

use RZP\Error\ErrorCode;

class ResponseCode
{
    public static $codes = array(
        'hash_mismatch'     => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        'Invalid OTP'       => ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
    );

    protected static $success = array(
        100, 'success'
    );

    public static function getResponseMessage($code)
    {
        $codes = self::$codes;

        return $codes[$code];
    }

    public static function getApiErrorCode($code)
    {
        $errorCodeClass = 'RZP\Error\ErrorCode::';

        if (empty($code) or (isset(self::$codes[$code]) === false))
        {
            return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
        }

        $apiCode = self::$codes[$code];

        if (defined($errorCodeClass . $apiCode))
        {
            return $apiCode;
        }

        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }
}
