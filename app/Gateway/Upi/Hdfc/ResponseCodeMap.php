<?php

namespace RZP\Gateway\Upi\Hdfc;

use RZP\Error\ErrorCode;

class ResponseCodeMap
{
    const CODES = [];

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
