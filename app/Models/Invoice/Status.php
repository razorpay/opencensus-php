<?php

namespace RZP\Models\Invoice;

class Status
{
    // ----------- Invoice Statuses -----------

    // Almost all attributes can be edited for invoice in draft status.
    const DRAFT         = 'draft';
    // Invoice when issued is payable by customer. And very few attributes can
    // be changed.
    const ISSUED        = 'issued';
    // Invoice has been paid.
    const PAID          = 'paid';
    // Invoice has been cancelled by the creator. It cannot be paid or viewed by
    // customers.
    const CANCELLED     = 'cancelled';
    // Invoice has been expired by our system as it has went past the expire_by
    // set for invoice.
    const EXPIRED       = 'expired';

    // ----------- End Invoice Statuses -----------

    // ----------- Invoice Sub Statuses -----------

    const ON_HOLD   = 'on_hold';

    // ----------- End Invoice Sub Statuses -----------

    // These statuses have corresponding timestamps column in invoice
    public static $timestampedStatuses = [
        self::ISSUED,
        self::PAID,
        self::CANCELLED,
        self::EXPIRED,
    ];

    public static $invoiceStatuses = [
        self::DRAFT,
        self::ISSUED,
        self::PAID,
        self::CANCELLED,
        self::EXPIRED,
    ];

    public static $subStatuses = [
        //
        // All the invoices created when the subscription
        // was on_hold, are not charged by our system.
        // Invoices created before and after the
        // subscription was on_hold are charged.
        //
        self::ON_HOLD,
    ];

    public static function isStatusValid($status) : bool
    {
        return in_array($status, self::$invoiceStatuses, true);
    }

    public static function checkStatus($status)
    {
        if (self::isStatusValid($status) === false)
        {
            throw new \InvalidArgumentException('Not a valid status: ' . $status);
        }
    }

    public static function isSubStatusValid($subStatus) : bool
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
