<?php

namespace RZP\Gateway\Wallet\Base;

class ResponseCode
{
    protected static $codes = array();

    public static function getResponseMessage($code)
    {
        $codes = static::$codes;

        return $codes[$code];
    }
}
