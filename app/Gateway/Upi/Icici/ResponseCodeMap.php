<?php

namespace RZP\Gateway\Upi\Icici;

use RZP\Error\ErrorCode;

class ResponseCodeMap
{
    const ERROR_CODES = array(
        5    => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        101  => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        5000 => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        5001 => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        5002 => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        5003 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ID,
        5004 => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        5005 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        5006 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_REFERENCE_NO,
        // Virtual address not present
        5007 => ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
        // PSP is not registered
        5008 => ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
        // Service unavailable. Please try later.
        5009 => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        5011 => ErrorCode::GATEWAY_ERROR_REFUND_DUPLICATE_REQUEST,
        5012 => ErrorCode::GATEWAY_ERROR_REFUND_DUPLICATE_REQUEST,

        5013 => ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
        5014 => ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE,

        // 8000-8010 are all JSON parsing or encryption errors
        8000 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        8001 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        8002 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        8003 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        8004 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        8005 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        8006 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        8007 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        8008 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        8009 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        8010 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        8011 => ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED,

        9999 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED
    );

    public static function getResponseMessage($code)
    {
        if (isset(self::ERROR_CODES[$code]) === true)
        {
            return self::ERROR_CODES[$code];
        }

        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }

    public static function getApiErrorCode($code)
    {
        if ((empty($code) === true) or
            (isset(self::ERROR_CODES[$code]) === false))
        {
            return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
        }

        return self::ERROR_CODES[$code];
    }
}
