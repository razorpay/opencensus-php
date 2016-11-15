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
