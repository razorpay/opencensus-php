<?php

namespace Gateway\Wallet\Olamoney;

use EE\Error;
use EE\Error\ErrorCode;

class ResponseCode
{
    public static $codes = array(
        'hash_mismatch'     => 'Hash verification failed',
        'user_not_found'    => 'User not found',
        '100'               => 'Transaction Successful',
        '101'               => 'User not logged in',
        '102'               => 'User has 0 balance',
        '103'               => 'User has insufficient balance',
        '104'               => 'Payment Failed',
        '105'               => 'Hash verification failed',
        '106'               => 'Duplicate transaction not allowed',
    );

    public static function getResponseMessage($code)
    {
        $codes = self::$codes;
        return $codes[$code];
    }
}
