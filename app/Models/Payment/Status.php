<?php

namespace RZP\Models\Payment;

class Status
{
    const CREATED       = 'created';
    const AUTHORIZED    = 'authorized';
    const CAPTURED      = 'captured';
    const FAILED        = 'failed';
    const REFUNDED      = 'refunded';

    protected static $statusList = [
        self::CREATED,
        self::AUTHORIZED,
        self::CAPTURED,
        self::FAILED,
        self::REFUNDED
    ];

    public static function isStatusValid($status)
    {
        return (defined(Status::class.'::'.strtoupper($status)));
    }

    public static function getStatusList()
    {
        return self::$statusList;
    }
}
