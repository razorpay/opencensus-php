<?php

namespace RZP\Gateway\Wallet\Base;

use RZP\Error\ErrorCode;

class ResponseCode
{
    protected static $codes = array();

    public static function getResponseMessage($code)
    {
        if (isset(static::$codes[$code]))
        {
            return static::$codes[$code];
        }
        else
        {
            return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
        }
    }
}
