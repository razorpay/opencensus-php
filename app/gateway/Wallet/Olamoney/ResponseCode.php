<?php

namespace Gateway\Wallet\Olamoney;

use EE\Error;
use EE\Error\ErrorCode;

class ResponseCode
{
    public static $codes = array(
        'hash_mismatch'  => 'Hash verification failed',
        'user_not_found' => 'User not found',
    );

    public static function getResponseMessage($code)
    {
        $codes = self::$codes;
        return $codes[$code];
    }
}
