<?php

namespace RZP\Gateway\Upi\Icici;

use RZP\Error\ErrorCode;

use RZP\Gateway\Base\ErrorCodes\Upi\ErrorCodes;

class ResponseCodeMap
{
    const ERROR_CODES = array(
        1    => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        4    => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        5    => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        9    => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_CUSTOMER,
        10   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        99   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
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
        5010 => ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING,
        5011 => ErrorCode::GATEWAY_ERROR_REFUND_DUPLICATE_REQUEST,
        5012 => ErrorCode::GATEWAY_ERROR_REFUND_DUPLICATE_REQUEST,

        5013 => ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
        5014 => ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_CREDITS,
        5017 => ErrorCode::GATEWAY_ERROR_TERMINAL_NOT_ENABLED,
        5019 => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        5020 => ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING,
        5021 => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        
        5023 => ErrorCode::GATEWAY_ERROR_MULTIPLE_REQUEST,
        5024 => ErrorCode::GATEWAY_ERROR_RECORD_NOT_FOUND,
        5025 => ErrorCode::GATEWAY_ERROR_REFUND_AMOUNT_INVALID,
        5026 => ErrorCode::GATEWAY_ERROR_CONSUMER_NUMBER_INVALID,
        5027 => ErrorCode::GATEWAY_ERROR_INVALID_MERCHANT_PREFIX,
        5029 => ErrorCode::GATEWAY_ERROR_NO_RESPONSE_FROM_SWITCH,
        5030 => ErrorCode::GATEWAY_ERROR_TECHNICAL_ERROR,

        // 8000-8008 are all JSON parsing or encryption errors
        8000 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        8001 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        8002 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        8003 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        8004 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        8005 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        8006 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        8007 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        8008 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,

        8009 => ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED,
        8010 => ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED,

        9999 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED
    );

    public static function getResponseMessage($code)
    {
        // Return Gateway Specific Error Code
        if (isset(self::ERROR_CODES[(int) $code]) === true)
        {
            return self::ERROR_CODES[$code];
        }

        // Return NPCI Error Code No need to type cast since it's a string
        if (isset(ErrorCodes::$errorCodeMap[$code]) === true)
        {
            return ErrorCodes::$errorCodeMap[$code];
        }

        // Return Unmapped Error Code Description
        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }

    public static function getApiErrorCode($code)
    {
        // Return NPCI Error Code, No Need to Type Case to string and check this.
        if (isset(ErrorCodes::$errorCodeMap[$code]) === true)
        {
            return ErrorCodes::$errorCodeMap[$code];
        }

        // self:ERROR_CODES
        if ((empty($code) === true) or
            (isset(self::ERROR_CODES[(int) $code]) === false))
        {
            return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
        }

        // Return Gateway Specific Error Code Description
        return self::ERROR_CODES[$code];
    }
}
