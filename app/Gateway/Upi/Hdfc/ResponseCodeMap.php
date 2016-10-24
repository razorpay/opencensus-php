<?php

namespace RZP\Gateway\Upi\Icici;

use RZP\Error\ErrorCode;

class ResponseCodeMap
{
    protected static $codes = array(

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
