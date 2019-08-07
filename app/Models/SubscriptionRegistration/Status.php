<?php

namespace RZP\Models\SubscriptionRegistration;


class Status
{
    const CREATED        = 'created';
    const INITIALIZED    = 'initialized';
    const AUTHENTICATED  = 'authenticated';
    const COMPLETED      = 'completed';

    protected static $statusList = [
        self::CREATED,
        self::INITIALIZED,
        self::AUTHENTICATED,
        self::COMPLETED
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
