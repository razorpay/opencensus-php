<?php

namespace RZP\Gateway\Wallet\Olamoney;

use RZP\Error\ErrorCode;

class ResponseCode
{
    public static $codes = array(
        'hash_mismatch'     => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        'user_not_found'    => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_USER_DOES_NOT_EXIST,
        '100'               => 'Transaction Successful',
        '101'               => 'User not logged in',
        '102'               => 'User has 0 balance',
        '103'               => 'User has insufficient balance',
        '104'               => 'Payment Failed',
        '105'               => 'Hash verification failed',
        '106'               => 'Duplicate transaction not allowed',
    );

    protected static $success = array(
        100, 'success'
    );

    public static function getResponseMessage($code)
    {
        $codes = self::$codes;

        return $codes[$code];
    }

    public static function getApiErrorCode($code)
    {
        $class = 'RZP\Error\ErrorCode::';

        if (empty($code) or (isset(self::$codes[$code]) === false))
        {
            return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
        }

        $apiCode = self::$codes[$code];

        if (defined($class . $apiCode))
        {
            return $apiCode;
        }

        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }
}
