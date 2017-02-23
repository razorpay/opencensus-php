<?php

namespace RZP\Gateway\Base;

use RZP\Error\ErrorCode;

class ResponseCodeMap
{
    protected static $codes = array();

    public static function getResponseMessage($code)
    {
        if (isset(static::$codes[$code]))
        {
            return static::$codes[$code];
        }

        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }

    public static function getApiErrorCode($code)
    {
        if ((empty($code) === true) or
            (isset(static::$codes[$code]) === false))
        {
            return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
        }

        return static::$codes[$code];
    }
}
