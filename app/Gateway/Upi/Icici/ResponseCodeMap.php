<?php

namespace RZP\Gateway\Upi\Icici;

use RZP\Error\ErrorCode;

class ResponseCodeMap
{
    protected static $codes = array(
        5    => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        5000 => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        5001 => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        5002 => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        5003 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ID,
        5004 => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        5005 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        5006 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_REFERENCE_NO,
        9999 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED
    );

    public static function getResponseMessage($code)
    {
        if (isset(self::$codes[$code]) === true)
        {
            return self::$codes[$code];
        }

        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }

    public static function getApiErrorCode($code)
    {
        if ((empty($code) === true) or
            (isset(self::$codes[$code]) === false))
        {
            return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
        }

        return self::$codes[$code];
    }
}
