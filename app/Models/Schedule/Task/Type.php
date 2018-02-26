<?php

namespace RZP\Models\Schedule\Task;

class Type
{
    const SETTLEMENT   = 'settlement';
    const SUBSCRIPTION = 'subscription';
    const PROMOTION    = 'promotion';
    const REPORTING    = 'reporting'; // Reporting Service

    const SYNC_LIVE_TEST = [
        self::SETTLEMENT,
    ];

    const TYPE_LIST = [
        self::SETTLEMENT,
        self::SUBSCRIPTION,
        self::PROMOTION,
    ];

    public static function isSyncedInLiveAndTest(string $type)
    {
        return (in_array($type, self::SYNC_LIVE_TEST, true) === true);
    }

    public static function isValid(string $type)
    {
        return (in_array($type, self::TYPE_LIST, true) === true);
    }
}
