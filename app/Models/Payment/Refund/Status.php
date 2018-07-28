<?php

namespace RZP\Models\Payment\Refund;

use RZP\Models\FundTransfer\Attempt;

class Status
{
    const CREATED   = 'created';
    const INITIATED = Attempt\Status::INITIATED;
    const PROCESSED = 'processed';
    const FAILED    = 'failed';

    const REFUND_STATUS = [
        self::CREATED,
        self::INITIATED,
        self::PROCESSED,
        self::FAILED,
    ];

    const TRACKABLE_STATUSES = [
        Status::PROCESSED,
        Status::FAILED
    ];

    public static function isStatusTrackedForMetrics($status)
    {
        if (in_array($status, Status::TRACKABLE_STATUSES))
        {
            return true;
        }

        return false;
    }
}
