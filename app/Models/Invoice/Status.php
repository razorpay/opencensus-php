<?php

namespace RZP\Models\Invoice;

use RZP\Exception\BadRequestValidationFailureException;

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

    const HALTED    = 'halted';

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

    public static $subscriptionStatuses = [
        //
        // All the invoices created when the subscription
        // was halted, are not charged by our system.
        // Invoices created before and after the
        // subscription was halted are charged.
        //
        self::HALTED,
    ];

    public static function isStatusValid($status) : bool
    {
        return in_array($status, self::$invoiceStatuses, true);
    }

    public static function checkStatus($status)
    {
        if (self::isStatusValid($status) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid status: ' . $status);
        }
    }

    public static function isSubscriptionStatusValid($subscriptionStatus) : bool
    {
        return in_array($subscriptionStatus, self::$subscriptionStatuses, true);
    }

    public static function checkSubscriptionStatus($subscriptionStatus)
    {
        if (self::isSubscriptionStatusValid($subscriptionStatus) === false)
        {
            throw new \InvalidArgumentException("Not a valid subscription status: " . $subscriptionStatus);
        }
    }
}
