<?php

namespace RZP\Gateway\Netbanking\Vijaya;

use DOMDocument;

class VerifyResponse
{
    const SUCCESS = 'Your Payment is Successful';
    const FAILURE = 'Payment Record Not Found Check the Parameters sent';

    const STATUS_LIST = [
        self::SUCCESS,
        self::FAILURE
    ];

    public static function isSuccess($content): bool
    {
        if (in_array(self::SUCCESS, $content))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}
