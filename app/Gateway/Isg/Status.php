<?php

namespace RZP\Gateway\Isg;

class Status
{
    const APPROVED          = '00';

    const NO_RECORDS        = '01';

    const FALLBACK          = '02';

    const SUCCESS           = 'Success';

    const FAILED            = 'Failed';

    protected static $statusCodeMap = [
        self::APPROVED      => 'Transaction Approved',
        self::NO_RECORDS    => 'Transaction Not Present',
        self::FALLBACK      => 'Transaction Declined',
    ];

    public static function getStatusCodeDescription($statusCode)
    {
        if (isset(self::$statusCodeMap[$statusCode]) === true)
        {
            return self::$statusCodeMap[$statusCode];
        }
    }
}
