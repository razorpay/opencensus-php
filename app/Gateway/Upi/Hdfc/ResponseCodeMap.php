<?php

namespace RZP\Gateway\Upi\Hdfc;

use RZP\Error\ErrorCode;

class ResponseCodeMap
{
    const CODES = [];

    public static function getResponseMessage($code)
    {
        return self::CODES[$code] ?? ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }

    public static function getApiErrorCode($code)
    {
        if ((empty($code) === true) or
            (isset(self::CODES[$code]) === false))
        {
            return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
        }

        return self::CODES[$code];
    }
}
