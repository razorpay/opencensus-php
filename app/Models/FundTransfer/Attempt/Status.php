<?php

namespace RZP\Models\FundTransfer\Attempt;

class Status
{
    const CREATED       = 'created';
    const INITIATED     = 'initiated';
    const FAILED        = 'failed';
    const PROCESSED     = 'processed';
    const REVERSED      = 'reversed';
    const FAILURE       = 'failure';
    const SUCCESS       = 'success';

    const PENDING_RECONCILIATION = self::INITIATED;

    const BULK_UPDATE_STATUSES = [
        self::FAILED,
        self::PROCESSED,
        self::INITIATED,
        self::CREATED,
        self::REVERSED,
    ];

    public static function isValidForBulkUpdate(string $status) : bool
    {
        return (in_array($status, self::BULK_UPDATE_STATUSES, true) === true);
    }
}
