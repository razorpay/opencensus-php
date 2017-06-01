<?php

namespace RZP\Models\Schedule\Task;

class Type
{
    const SETTLEMENT   = 'settlement';
    const SUBSCRIPTION = 'subscription';

    const SYNC_LIVE_TEST = [
        self::SETTLEMENT,
    ];

    public static function isSyncedInLiveAndTest(string $type)
    {
        return (in_array($type, self::SYNC_LIVE_TEST, true) === true);
    }
}
