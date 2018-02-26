<?php

namespace RZP\Models\Schedule\Task;

use RZP\Services\Reporting;

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
        self::REPORTING
    ];

    const EXTERNAL_SERVICES = [
        self::REPORTING => Reporting::class
    ];

    public static function isSyncedInLiveAndTest(string $type)
    {
        return (in_array($type, self::SYNC_LIVE_TEST, true) === true);
    }

    public static function isValid(string $type)
    {
        return (in_array($type, self::TYPE_LIST, true) === true);
    }

    public static function isValidService(string $type)
    {
        return (in_array($type, array_keys(self::EXTERNAL_SERVICES), true) === true);
    }

    public static function getServiceClass(string $type)
    {
        $className = self::EXTERNAL_SERVICES[$type];

        return new $className();
    }
}
