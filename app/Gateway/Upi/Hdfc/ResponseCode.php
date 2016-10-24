<?php

namespace RZP\Gateway\Upi\Hdfc;

use RZP\Error;
use RZP\Error\ErrorCode;

class ResponseCode
{
    public static $codes = array(

    );

    public static function getResponseMessage($code)
    {
        $codes = self::$codes;

        if (array_key_exists($code, $codes))
        {
            return $codes[$code];
        }
        else
        {
            return 'Unknown Gateway Response Code';
        }
    }
}
