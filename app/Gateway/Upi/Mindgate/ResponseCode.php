<?php

namespace RZP\Gateway\Upi\Mindgate;

use RZP\Error;
use RZP\Error\ErrorCode;

class ResponseCode
{
    /**
     * We list these as per the docs given to us
     * However, these are never returned in reality
     * @var array
     */
    const CODES = [
        'FAILED'    =>  'Payment Failed because of Gateway Error',
    ];

    public static function getResponseMessage($code)
    {
        return self::CODES[$code] ?? 'Unknown Gateway Response Code';
    }
}
