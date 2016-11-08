<?php

namespace RZP\Gateway\Upi\Icici;

use RZP\Error;

class ResponseCode
{
    public static $codes = array(
        5    => 'The amount given is invalid',
        101  => 'Unknown Server Error',
        5000 => 'The request has failed with reasons not listed below',
        5001 => 'The merchant Id is not valid',
        5002 => 'Transaction is already initiated with this merchant transaction id',
        5003 => 'Merchant transaction id is null',
        5004 => 'Invalid packet',
        5005 => 'Given collect by date is less than current date',
        5006 => 'No transaction initiated with given transaction id based on merchant id',
        9999 => 'No response from Bank',
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
