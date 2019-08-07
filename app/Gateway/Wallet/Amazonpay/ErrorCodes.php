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
        '301' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        '302' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        '230' => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        '231' => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT,
        '101' => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT_AT_GATEWAY,
        '310' => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT,
        '103' => ErrorCode::GATEWAY_ERROR_TERMINAL_NOT_ENABLED,
        '104' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID,
        '600' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_ACCOUNT_INACTIVE,
    ];

    public static function getInternalErrorCode(string $reasonCode)
    {
        if (isset(self::$reasonCodeToErrorCodeMap[$reasonCode]) === true)
        {
            return self::$reasonCodeToErrorCodeMap[$reasonCode];
        }

        return ErrorCode::GATEWAY_ERROR_FATAL_ERROR;
    }
}
