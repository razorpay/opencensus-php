<?php

namespace Gateway\Wallet\Olamoney;

use EE\Error;
use EE\Error\ErrorCode;

class ResponseCode
{
    public static $codes = array(
        Status::SUCCESS         =>  'Transaction Successful',
        Status::NLOGGEDIN       =>  'User not logged in',
        Status::NOBALANCE       =>  'User has 0 balance',
        Status::INSUFFICIENTBALANCE =>  'User has insufficient balance',
        Status::FAILED          =>  'Payment Failed',
        Status::HASHFAILED      =>  'Hash verification failed',
        Status::DUPLICATE       =>  'Duplicate transaction not allowed',
    );

    public static function getResponseMessage($code)
    {
        $codes = self::$codes;
        return $codes[$code];
    }
}
