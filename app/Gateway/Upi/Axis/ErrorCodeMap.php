<?php

namespace RZP\Gateway\Upi\Axis;

use RZP\Error\ErrorCode;

class ErrorCodeMap
{
    const CODES = [
        Status::VPA_NOT_AVAILABLE => ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
        Status::FAILURE           => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        Status::FAILED            => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        Status::PENDING           => ErrorCode::BAD_REQUEST_PAYMENT_PENDING,
        Status::TIMEOUT           => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT,
    ];

    const CODESMAP = [
        Status::FAILURE           => 'Payment Failed because of Gateway Error',
        Status::FAILED            => 'Payment Failed because of Gateway Error',
        Status::VPA_NOT_AVAILABLE => 'Vpa not available',
        Status::PENDING           => 'Transaction pending',
        Status::TIMEOUT           => 'Transaction timed out',
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

    public static function getResponseMessage($code)
    {
        return self::CODES[$code] ?? 'Unknown Gateway Response Code';
    }
}
