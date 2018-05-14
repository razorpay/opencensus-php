<?php

namespace RZP\Gateway\Atom;

use RZP\Error\ErrorCode;

class ResponseCode
{
    public static $codes = [
        'M0' => 'Invalid merchant',
        'M1' => 'Refund is not allowed',
        'M2' => 'Invalid Transaction ID or Failed Transaction',
        'M3' => 'Invalid amount',
        'M4' => 'Insufficient amount in the account to be refund',
        'M5' => 'Invalid transaction date',
    ];

    public static $codeMap = [
        'M0' => ErrorCode::BAD_REQUEST_MERCHANT_INVALID,
        'M1' => ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED,
        'M2' => ErrorCode::GATEWAY_ERROR_PAYMENT_TRANSACTION_NOT_FOUND,
        'M3' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        'M4' => ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED,
        'M5' => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_TRANSACTION_DATE,
    ];

    public static function getMappedCode($code)
    {
        if (isset(self::$codeMap[$code]))
        {
            return self::$codeMap[$code];
        }

        return ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR;
    }
}
