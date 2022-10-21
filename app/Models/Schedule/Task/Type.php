<?php

namespace RZP\Models\Schedule\Task;

use RZP\Services\Reporting;

class Type
{
    const SETTLEMENT   = 'settlement';
    const SUBSCRIPTION = 'subscription';
    const PROMOTION    = 'promotion';
    const REPORTING    = 'reporting'; // Reporting Service

    // Used for fee recovery payouts for RX Current Accounts
    const FEE_RECOVERY = 'fee_recovery';

    const CDS_PRICING  = 'cds_pricing';

    const LOG          = 'log';

    const SYNC_LIVE_TEST = [
        self::SETTLEMENT,
    ];

    const TYPE_LIST = [
        self::SETTLEMENT,
        self::SUBSCRIPTION,
        self::PROMOTION,
        self::REPORTING,
        self::FEE_RECOVERY,
        self::CDS_PRICING,
    ];

    const EXTERNAL_SERVICES = [
        self::REPORTING => Reporting::class
    ];

    const TYPE_ENTITY_MAP = [
        self::REPORTING => [
            self::LOG
        ]
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

    public static function isValidEntityType(string $type, $entityType)
    {
        return (in_array($entityType, self::TYPE_ENTITY_MAP[$type], true) === true);
    }
}
