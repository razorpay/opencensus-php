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
    const SYNC_IN_PROGRESS    = 'sync_in_progress';
    const SYNC_SUCCESS        = 'sync_success';
    const SYNC_FAILED         = 'sync_failed';

    protected static $values = [
        self::NOT_SYNCED       => 0,
        self::SYNC_IN_PROGRESS => 1,
        self::SYNC_SUCCESS     => 2,
        self::SYNC_FAILED      => 3,
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
