<?php

namespace RZP\Gateway\Netbanking\Airtel;

use RZP\Error\ErrorCode;

class ErrorCodes
{
    CONST SUCCESS      = '000';
    CONST RANDOM_ERROR = '10000';
    CONST TRANSACTION_NOT_PRESENT = '910';

    protected static $errorCodeDesc = [
        '000'       => 'Success',
        '1'         => 'We were not able to capture all your details',
        '401'       => 'Your Wallet Creation Attempts exhausted',
        '501'       => 'Your Wallet Creation Attempts exhausted',
        '900'       => 'Transaction cancelled by user',
        '902'       => 'Invalid MID in Request',
        '905'       => 'Invalid input in Success / Failure URL',
        '909'       => 'Invalid Currency, only INR supported',
        '910'       => 'Transaction not present in Airtel Payments Bank',
        '912'       => 'Invalid input amount',
        '913'       => 'Input amount is negative',
        '920'       => 'Invalid Transaction Id / Date',
        '923'       => 'Sum of all reversal amounts is greater than transaction amount',
        '999'       => 'Any other Airtel Payment Bank failure',
        '9002'      => 'Invalid parameter in request',
        '10000'     => 'Random Authorization Error',
        '999999'    => 'Any other Airtel Payment Bank failure'
    ];

    protected static $errorMessageMap = [
        '1'         => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        '401'       => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        '501'       => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        '900'       => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_NETBANKING_PAYMENT_PAGE,
        '902'       => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL,
        '905'       => ErrorCode::GATEWAY_ERROR_INVALID_CALLBACK_URL,
        '909'       => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY,
        '910'       => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ID,
        '912'       => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        '913'       => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        '920'       => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        '923'       => ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED,
        '999'       => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        '9002'      => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        '999999'    => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
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

        return ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR;
    }
}
