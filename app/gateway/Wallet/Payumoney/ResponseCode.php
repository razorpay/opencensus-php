<?php

namespace Gateway\Wallet\Payumoney;

use EE\Error;
use EE\Error\ErrorCode;

class ResponseCode
{
    public static $codes = array(
        3010007 => 'Verification code is incorrect',
        3010008 => 'Verification code has expired - Please generate a new verification code',
    );

    public static function getResponseMessage($code)
    {
        $codes = self::$codes;

        return $codes[(int)$code];
    }
}