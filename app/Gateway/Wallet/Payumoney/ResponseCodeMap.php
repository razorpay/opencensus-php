<?php

namespace RZP\Gateway\Wallet\Payumoney;

use RZP\Error;
use RZP\Error\ErrorCode;

class ResponseCodeMap
{
    public static $codes = array(
        2010006 => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_ACCOUNT_INVALID_CREDENTIALS,
        2010009 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_MOBILE,
        2010013 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_EMAIL,
        2010015 => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_CONTACT_PAYUMONEY,
        3010006 => ErrorCode::BAD_REQUEST_PAYMENT_OTP_VALIDATION_ATTEMPT_LIMIT_EXCEEDED,
        3010007 => ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
        3010008 => ErrorCode::BAD_REQUEST_PAYMENT_OTP_EXPIRED,
        3010032 => ErrorCode::BAD_REQUEST_PAYMENT_OTP_VALIDATION_ATTEMPT_LIMIT_EXCEEDED
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
            (isset(self::$codes[$code]) === false))
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