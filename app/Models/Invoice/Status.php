<?php

namespace RZP\Models\Invoice;

class Status
{
    // ----------- Invoice Statuses -----------
    const DRAFT         = 'draft';
    const ISSUED        = 'issued';
    // NOTE: This status is being used in the index.blade.php file too to display a message.
    const PAID          = 'paid';
    const EXPIRED       = 'expired';
    const DELETED       = 'deleted';

    // ----------- End Invoice Statuses -----------

    // ----------- Invoice Sub Statuses -----------

    const ON_HOLD   = 'on_hold';

    // ----------- End Invoice Sub Statuses -----------

    // These statuses have corresponding timestamps column in invoice
    public static $timestampedStatuses = [
        self::ISSUED,
        self::PAID,
        self::EXPIRED,
    ];

    public static $subStatuses = [
        self::ON_HOLD,
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

    public static function isSubStatusValid($subStatus)
    {
        return in_array($subStatus, self::$subStatuses, true);
    }

    public static function checkSubStatus($subStatus)
    {
        if (self::isSubStatusValid($subStatus) === false)
        {
            throw new \InvalidArgumentException("Not a valid sub status: " . $subStatus);
        }
    }
}
