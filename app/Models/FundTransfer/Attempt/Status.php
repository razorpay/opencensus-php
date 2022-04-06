<?php

namespace RZP\Models\FundTransfer\Attempt;

class Status
{
    const CREATED       = 'created';
    const INITIATED     = 'initiated';
    const FAILED        = 'failed';
    const PROCESSED     = 'processed';
    const REVERSED      = 'reversed';

    const PENDING_RECONCILIATION = self::INITIATED;

    const STATUSES = [
        self::FAILED,
        self::PROCESSED,
        self::INITIATED,
        self::CREATED,
        self::REVERSED,
    ];

    const TERMINAL_STATUSES = [
        self::FAILED,
        self::PROCESSED,
        self::REVERSED,
    ];

    // allowed state transition when webhook is fired from fts
    const ALLOWED_STATE_TRANSITION = [
        self::CREATED   => [self::CREATED, self::INITIATED, self::PROCESSED, self::FAILED],
        self::INITIATED => [self::INITIATED, self::FAILED, self::PROCESSED, self::REVERSED],
        self::PROCESSED => [self::PROCESSED, self::REVERSED],
        self::REVERSED  => [self::REVERSED],
        self::FAILED    => [self::FAILED],
    ];

    public static function isValidForBulkUpdate(string $status) : bool
    {
        return (in_array($status, self::STATUSES, true) === true);
    }

    // checks if state transition is possible or not when webhook got fired from fts transfer
    public static function isValidStateTransition(string $currentStatus, string $nextStatus) : bool
    {
        return (in_array($nextStatus, self::ALLOWED_STATE_TRANSITION[$currentStatus], true) === true);
    }
}
