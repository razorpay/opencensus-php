<?php


namespace RZP\Models\CreditTransfer;


class Status
{
    // max length in db column is 14
    const CREATED   = 'created';
    const FAILED    = 'failed';
    const PROCESSED = 'processed';

    /**
     * These statuses have corresponding timestamps column in credit_transfer (_at)
     * created_at, updated_at are handled in the base class
     *
     * @var array
     */
    public static $timestampedStatuses = [
        self::PROCESSED,
        self::FAILED,
    ];
}
