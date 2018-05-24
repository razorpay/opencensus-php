<?php

namespace RZP\Gateway\Wallet\Amazonpay;

use RZP\Error\ErrorCode;

final class ErrorCodes
{
    /**
     * Maps AmazonPay error codes to Razorpay Error Codes
     * @var array
     */
    private static $reasonCodeToErrorCodeMap = [
        '01'  => ErrorCode::BAD_REQUEST_PAYMENT_PENDING,
        '229' => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
        '211' => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK,
        '100' => ErrorCode::BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD,
        '301' => ErrorCode::BAD_REQUEST_INVALID_PARAMETERS,
        '302' => ErrorCode::BAD_REQUEST_INVALID_PARAMETERS,
        '230' => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        '231' => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT,
        '101' => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT_AT_GATEWAY,
        '310' => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT,
        '103' => ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED,
        '104' => ErrorCode::BAD_REQUEST_MERCHANT_INVALID,
    ];

    public static function getInternalErrorCode(string $reasonCode)
    {
        if (isset(self::$reasonCodeToErrorCodeMap[$reasonCode]) === true)
        {
            return self::$reasonCodeToErrorCodeMap[$reasonCode];
        }

        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }
}
