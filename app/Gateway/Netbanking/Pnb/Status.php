<?php

namespace RZP\Gateway\Netbanking\Pnb;

class Status
{
    // Transaction status codes. This is defined by us. Bank sends code, 0 -> success
    const SUCCESS = '0';

    public static function isSuccess($status)
    {
        return (string) $status === self::SUCCESS;
    }
}
