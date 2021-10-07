<?php

namespace RZP\Models\Payment;

class Status
{
    const CREATED       = 'created';
    const AUTHENTICATED = 'authenticated';
    const AUTHORIZED    = 'authorized';
    const CAPTURED      = 'captured';
    const FAILED        = 'failed';
    const REFUNDED      = 'refunded';
    const PENDING       = 'pending';

    protected static $statusList = [
        self::CREATED,
        self::AUTHENTICATED,
        self::AUTHORIZED,
        self::CAPTURED,
        self::FAILED,
        self::REFUNDED,
        self::PENDING,
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
