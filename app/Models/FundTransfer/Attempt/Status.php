<?php

namespace RZP\Models\FundTransfer\Attempt;

class Status
{
    const CREATED       = 'created';
    const INITIATED     = 'initiated';
    const FAILED        = 'failed';
    const PROCESSED     = 'processed';

    const PENDING_RECONCILIATION = self::INITIATED;

    const BULK_UPDATE_STATUSES = [
        self::FAILED,
        self::PROCESSED,
    ];

    public static function isValidForBulkUpdate(string $status) : bool
    {
        return (in_array($status, self::BULK_UPDATE_STATUSES, true) === true);
    }
}
