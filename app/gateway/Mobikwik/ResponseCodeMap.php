<?php

namespace Gateway\Mobikwik;

use EE\Error;
use EE\Error\ErrorCode;

class ResponseCodeMap
{
    public static $codes = array(
        0  => 'Transaction completed successfully',
        10 => 'Merchant secret key does not exist',
        20 => 'User Blocked',
        21 => 'Merchant Blocked',
        22 => 'Merchant does not Exist',
        23 => 'Merchant not registered on MobiKwik',
        24 => 'Orderid is Blank or Null',
        30 => 'Wallet TopUp Failed',
        31 => 'Wallet Debit Failed',
        32 => 'Wallet Credit Failed',
        33 => 'User does not have sufficient balance in his wallet',
        40 => 'User canceled transaction at Login page',
        41 => 'User canceled transaction at Wallet Top Up page',
        42 => 'User canceled transaction at Wallet Debit page',
        50 => 'Order Id already processed with this merchant.',
        51 => 'Length of parameter orderid must be between 8 to 30 characters',
        52 => 'Parameter orderid must be alphanumeric only',
        53 => 'Parameter email is invalid',
        54 => 'Parameter amount must be integer only',
        55 => 'Parameter cell is invalid. It must be numeric, have 10 digits and start with 7,8,9',
        56 => 'Parameter merchantname is invalid. It must be alphanumeric and its length must be between 1 to 30 characters',
        57 => 'Parameter redirecturl is invalid',
        60 => 'User Authentication failed',
        70 => 'Monthly Wallet Top up limit crossed',
        71 => 'Monthly transaction limit for this user crossed',
        72 => 'Maximum amount per transaction limit for this merchant crossed',
        73 => 'Merchant is not allowed to perform transactions on himself',
        74 => 'KYC Transactions is not allowed',
        80 => 'Checksum Mismatch',
        99 => 'Unexpected Error'
    );

    protected static $success = array(
        1, 8,
    );

    public static function getResponseMessage($code)
    {
        $codes = self::$codes;

        return $codes[(int)$code];
    }

    public static function getStatus($code)
    {
        ; // @todo
    }

    public static function getApiErrorCode($code)
    {
        $class = 'EE\Error\ErrorCode::';

        if (isset(self::$codes[$code]) === false) {
            return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
        }

        $apiCode = self::$codes[$code];

        if (defined($class . $apiCode)) {
            return $apiCode;
        }

        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }
}