<?php

namespace RZP\Models\Payout;

use RZP\Models\FundTransfer\Attempt;

class Status
{
    // The following three constants are required by the
    // FTA module to update the source status. Things will
    // get wrecked if these are removed. Wrecked.

    const PROCESSED  = 'processed';
    const INITIATED  = Attempt\Status::INITIATED;
    const FAILED     = 'reversed';


    const CREATED    = 'created';
    const REVERSED   = 'reversed';

    /**
     * Used only to expose publicly.
     * It's used in place of created/initiated.
     */
    const PROCESSING = 'processing';

    public static $internalToExternalStatusMapping = [
        self::CREATED   => self::PROCESSING,
        self::INITIATED => self::PROCESSING,
        self::PROCESSED => self::PROCESSED,
        self::REVERSED  => self::REVERSED,
    ];

    public static function getPublicStatusFromInternalStatus($internalStatus)
    {
        return static::$internalToExternalStatusMapping[$internalStatus] ?? $internalStatus;
    }
}
