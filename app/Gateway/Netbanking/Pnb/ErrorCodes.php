<?php

namespace RZP\Gateway\Netbanking\Pnb;

use RZP\Error\ErrorCode;

class ErrorCodes
{
    protected static $errorCodeMap = [
        'P' => ErrorCode::BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION,
        'F' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
    ];

    public static function getErrorCodeMap($errorCode)
    {
        if (isset(self::$errorCodeMap[$errorCode]) === true)
        {
            return self::$errorCodeMap[$errorCode];
        }

        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }
}
