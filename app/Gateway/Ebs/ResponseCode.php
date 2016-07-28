<?php

namespace RZP\Gateway\Ebs;

use RZP\Error\ErrorCode;

class ResponseCode
{

    const UNKNOWN_ERROR     = 'Unknown Error';

    public static $reasonCodes = array(
        0  => 'Successful transaction',
        23 => 'Capture request is not processed yet',
        26 => 'This amount can not be refunded',
        29 => 'Insufficient balance',
    );

    public static $errorCodeMap = array(
        23 => ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED,
        26 => ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED,
        29 => ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE,
    );

    public static function getMappedCode($code)
    {
        if (isset(self::$errorCodeMap[$code]))
        {
            return self::$errorCodeMap[$code];
        }
        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }
}
