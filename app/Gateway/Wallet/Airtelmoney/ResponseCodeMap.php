<?php

namespace RZP\Gateway\Wallet\Airtelmoney;

use RZP\Error;
use RZP\Error\ErrorCode;

class ResponseCodeMap
{
    public static $codes = array(
        '901'   => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID,
        '905'   => ErrorCode::GATEWAY_ERROR_INVALID_CALLBACK_URL,
        '909'   => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY,
        '912'   => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        '913'   => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        '920'   => ErrorCode::GATEWAY_ERROR_INVALID_DATE_FORMAT,
        '931'   => ErrorCode::GATEWAY_ERROR_INVALID_DATE_FORMAT,
        '923'   => ErrorCode::GATEWAY_ERROR_PAYMENT_CREDIT_LESS_THAN_DEBIT,
        '930'   => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID,
        '999'   => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        '13365' => ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED,
        '14236' => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
    );

    public static function getApiErrorCode($code)
    {
        $class = 'RZP\Error\ErrorCode::';

        if ((empty($code) === true) or
            (isset(self::$codes[$code]) === false))
        {
            return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
        }

        $apiCode = self::$codes[$code];

        return $apiCode;
    }
}
