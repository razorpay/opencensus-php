<?php

namespace RZP\Gateway\Upi\Mindgate;

use RZP\Error\ErrorCode;

class ResponseCodeMap
{
    const CODES = [
        Status::VPA_NOT_AVAILABLE => ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
        Status::FAILURE           => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        Status::FAILED            => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        Status::PENDING           => ErrorCode::BAD_REQUEST_PAYMENT_PENDING,
        Status::TIMEOUT           => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT,
        Status::EXPIRED           => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT,
    ];

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
