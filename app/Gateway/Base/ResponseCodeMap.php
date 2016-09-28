<?php

namespace RZP\Gateway\Base;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;

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
        $class = 'RZP\Error\ErrorCode::';

        if ((empty($code) === true) or
              (isset(static::$codes[$code]) === false))
        {
            return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
        }

        $apiCode = static::$codes[$code];

        if (defined($class . $apiCode))
        {
            return $apiCode;
        }

        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }
}
