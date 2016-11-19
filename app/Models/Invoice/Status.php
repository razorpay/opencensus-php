<?php

namespace RZP\Models\Invoice;

class Status
{
    // -------- Invoice Statuses -----------
    const DRAFT         = 'draft';
    const ISSUED        = 'issued';
    const PAID          = 'paid';
    const EXPIRED       = 'expired';
    const DELETED       = 'deleted';

    // These statuses have corresponding timestamps column in invoice
    public static $timestampedStatuses = [
        self::ISSUED,
        self::PAID,
        self::EXPIRED,
    ];

    public static function isStatusValid($status)
    {
        return (defined(__CLASS__ . '::' . strtoupper($status)));
    }

    public static function checkStatus($status)
    {
        if (self::isStatusValid($status) === false)
        {
            throw new \InvalidArgumentException('Not a valid status: ' . $status);
        }
    }
}
