<?php

namespace RZP\Gateway\Netbanking\Pnb;

class Status
{
    // transaction status codes
    const SUCCESS = 'S';
    const FAIL    = 'F';
    const PENDING = 'P';

    public static function getAuthSuccessStatus()
    {
        return self::SUCCESS;
    }
}
