<?php

namespace Gateway\Wallet\Payumoney;

use EE\Error;
use EE\Error\ErrorCode;

class ResponseCodeMap
{
    public static $codes = array(
        2010009 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_MOBILE,
        2010013 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_EMAIL,
        2010015 => 'Please contact care@payumoney.com using your registered email ID',
        3010006 => 'Please retry after 2 minutes',
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
        $class = 'EE\Error\ErrorCode::';

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