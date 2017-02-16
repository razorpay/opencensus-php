<?php

namespace RZP\Gateway\Wallet\Payumoney;

use RZP\Error;

class ResponseCode
{
    public static $codes = array(
        2010006 => 'Invalid Credentials on PayUmoney',
        2010009 => 'Invalid Mobile Number',
        2010013 => 'Invalid Email Id',
        2010015 => 'Please contact care@payumoney.com using your registered email ID',
        3010006 => 'Please retry after 2 minutes',
        3010007 => 'Invalid verification code. Please try again',
        3010008 => 'Verification code has expired - Please generate a new verification code',
        3010032 => 'You have exhausted your retry attempts. Contact care@payumoney.com for more information',
    );

    public static function getResponseMessage($code)
    {
        $codes = self::$codes;

        return $codes[$code];
    }
}
