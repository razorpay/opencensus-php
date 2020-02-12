<?php


namespace RZP\Models\Terminal;


/**
 * Class SyncStatus
 * Enum values used for status while syncing api terminals with terminals service
 * @package RZP\Models\Terminal
 */
class SyncStatus
{

    const NOT_SYNCED          = 'not_synced';
    const SYNC_SUCCESS        = 'sync_success';
    const SYNC_FAILED         = 'sync_failed';

    protected static $values = [
        self::NOT_SYNCED   => 0,
        self::SYNC_SUCCESS => 1,
        self::SYNC_FAILED  => 2,
    ];

    public static function getValueForSyncStatusString(string $syncStatus)
    {
        return self::$values[$syncStatus];
    }

    public static function getSyncStatusStringForValue(int $syncStatusValue)
    {
        $values = array_flip(self::$values);

        return $values[$syncStatusValue];
    }
}
