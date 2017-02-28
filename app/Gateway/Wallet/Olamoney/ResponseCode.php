<?php

namespace RZP\Gateway\Wallet\Olamoney;

use RZP\Error\ErrorCode;

class ResponseCode
{
    public static $codes = [
        'hash_mismatch'                                                                                 =>
            ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        'Invalid OTP'                                                                                   =>
            ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
        'Invalid user access token'                                                                     =>
            ErrorCode::BAD_REQUEST_PAYMENT_WALLET_AUTHENTICATION_FAILED,
        'The email ID provided is already registered with us. Please try with a different email ID.'    =>
            ErrorCode::BAD_REQUEST_PAYMENT_WALLET_ACCOUNT_INVALID_CREDENTIALS,
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
