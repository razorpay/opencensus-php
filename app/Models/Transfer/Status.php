<?php

namespace RZP\Models\Transfer;

class Status
{
    const CREATED                   = 'created';
    const PENDING                   = 'pending';
    const PROCESSED                 = 'processed';
    const FAILED                    = 'failed';
    const REVERSED                  = 'reversed';
    const PARTIALLY_REVERSED        = 'partially_reversed';

    public static $forSettlementStatusUpdate = [
        self::PROCESSED,
        self::PARTIALLY_REVERSED,
        self::REVERSED,
    ];

    public static function isStatusValid($status)
    {
        return (defined(__CLASS__ . '::' . strtoupper($status)));
    }
}
