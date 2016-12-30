<?php

namespace RZP\Gateway\Netbanking\Airtel;

use RZP\Error\ErrorCode;

class ErrorCodes
{
    protected static $errorCodeDesc = [
        '902'       => 'Invalid MID in Request',
        '905'       => 'Invalid input in Success / Failure URL',
        '909'       => 'Invalid Currency, only INR supported',
        '910'       => 'Transaction not present in Airtel Payments Bank',
        '912'       => 'Invalid input amount',
        '913'       => 'Input amount is negative',
        '920'       => 'Invalid Transaction Id / Date',
        '923'       => 'Sum of all reversal amounts is greater than transaction amount',
        '9002'      => 'Invalid parameter in request',
        '999999'    => 'Any other Airtel Payment Bank failure'
    ];

    protected static $errorMessageMap = [
        '902'       => ErrorCode::BAD_REQUEST_INVALID_ID,
        '905'       => ErrorCode::GATEWAY_ERROR_INVALID_CALLBACK_URL,
        '909'       => ErrorCode::BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED,
        '910'       => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        '912'       => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        '913'       => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        '920'       => ErrorCode::BAD_REQUEST_INVALID_PARAMETERS,
        '923'       => ErrorCode::BAD_REQUEST_PAYMENT_REFUND_AMOUNT_GREATER_THAN_CAPTURED,
        '9002'      => ErrorCode::BAD_REQUEST_INVALID_PARAMETERS,
        '999999'    => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
    ];

    public static function getErrorCodeDescription($errorCode)
    {
        if (isset(self::$errorCodeDesc[$errorCode]) === true)
        {
            return self::$errorCodeDesc[$errorCode];
        }

        return null;
    }

    public static function getErrorCodeMap($errorCode)
    {
        if (isset(self::$errorMessageMap[$errorCode]) === true)
        {
            return self::$errorMessageMap[$errorCode];
        }

        return ErrorCode::GATEWAY_ERROR_REQUEST_ERROR;
    }
}
