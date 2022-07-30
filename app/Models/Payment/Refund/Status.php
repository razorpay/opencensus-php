<?php

namespace RZP\Models\Payment\Refund;

use RZP\Models\FundTransfer\Attempt;

class Status
{
    const CREATED    = 'created';
    const INITIATED  = Attempt\Status::INITIATED;
    const PROCESSED  = 'processed';
    const FAILED     = 'failed';
    const REVERSED   = 'reversed';
    // This is a derived status
    const PENDING    = 'pending';
    // This is a public status shown for instant refunds dashboard merchants
    const PROCESSING = 'processing';

    const REFUND_STATUS = [
        self::CREATED,
        self::INITIATED,
        self::PROCESSED,
        self::FAILED,
        self::REVERSED
    ];

    const REFUND_NON_FAILURE_STATUS = [
        self::CREATED,
        self::INITIATED,
        self::PROCESSED
    ];

    const TRACKED_STATUSES = [
        Status::PROCESSED,
        Status::FAILED,
        Status::REVERSED
    ];

    public static function isStatusTrackedForMetrics(string $status): bool
    {
        return (in_array($status, Status::TRACKED_STATUSES, true) === true);
    }
}
