<?php

namespace RZP\Gateway\Wallet\Olamoney;

use RZP\Error\ErrorCode;

class ResponseCode
{
    public static $codes = [
        'Invalid OTP'                                                                                   =>
            ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
        'Invalid user access token'                                                                     =>
            ErrorCode::BAD_REQUEST_PAYMENT_WALLET_AUTHENTICATION_FAILED,
        'The email ID provided is already registered with us. Please try with a different email ID.'    =>
            ErrorCode::BAD_REQUEST_PAYMENT_WALLET_ACCOUNT_INVALID_CREDENTIALS,
        'signature_validation_failed'                                                                   =>
            ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,
        'hash_mismatch'                                                                                 =>
            ErrorCode::SERVER_ERROR_HASH_MISMATCH,
        'insufficient_funds'                                                                            =>
            ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE,
        'duplicate_command'                                                                             =>
            ErrorCode::GATEWAY_ERROR_DUPLICATE_TRANSACTION,
        'instrument_not_available'                                                                      =>
            ErrorCode::GATEWAY_ERROR_PAYMENT_INSTRUMENT_NOT_ENABLED,
        'user_merchant_limit_breached'                                                                  =>
            ErrorCode::GATEWAY_ERROR_USER_MERCHANT_LIMIT_BREACHED,
        'user_blocked'                                                                                  =>
            ErrorCode::BAD_REQUEST_PAYMENT_WALLET_BLOCKED_CUSTOMER,
        'invalid_parameter'                                                                             =>
            ErrorCode::BAD_REQUEST_INVALID_PARAMETERS,
        'outstanding_limit_breached'                                                                    =>
            ErrorCode::GATEWAY_ERROR_OUTSTANDING_LIMIT_BREACHED,
        'invalid_request'                                                                               =>
            ErrorCode::BAD_REQUEST_INVALID_PARAMETERS,
    ];

    protected static $success = [
        100, 'success'
    ];

    public static function getResponseMessage($code)
    {
        $codes = self::$codes;

        return $codes[$code];
    }

    public static function getApiErrorCode($code)
    {
        $errorCodeClass = 'RZP\Error\ErrorCode::';

        if (empty($code) or (isset(self::$codes[$code]) === false))
        {
            return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
        }

        return self::$codes[$code];
    }
}
