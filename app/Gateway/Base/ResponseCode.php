<?php

namespace RZP\Gateway\Base;

use RZP\Error\PublicErrorDescription;

class ResponseCode
{
    protected static $codes = array();

    public static function getResponseMessage($code)
    {
        if (isset(static::$codes[$code]))
        {
            return static::$codes[$code];
        }

        return PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED;
    }
}
