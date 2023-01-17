<?php

namespace RZP\Models\Invoice;

use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    // ----------- Invoice Statuses ----------------------------------

    // DRAFT:          Almost all attributes can be edited for invoice.
    // ISSUED:         Invoice is payable by customer and a few attributes can be edited.
    // PARTIALLY_PAID: Invoice is paid in partial. More payments can be accepted.
    // PAID:           Invoice is paid in full.
    // CANCELLED:      Invoice is cancelled by merchant and is not payable.
    // EXPIRED:        Invoice is expired by our system as it has went pas the expire_by.

    const DRAFT          = 'draft';
    const ISSUED         = 'issued';
    const PARTIALLY_PAID = 'partially_paid';
    const PAID           = 'paid';
    const CANCELLED      = 'cancelled';
    const EXPIRED        = 'expired';

    // CREATED:       Default status on creating the entity.
    // INITIATED:     Process to generate PDF Invoice started.
    // GENERATED:     Invoice successfully generated and can be downloaded.
    // FAILED:        Invoice generation failed.

    const CREATED        = 'created';
    const INITIATED      = 'initiated';
    const GENERATED      = 'generated';
    const FAILED         = 'failed';

    // ----------- End Invoice Statuses ------------------------------

    // ----------- Invoice subscription statuses ---------------------

    const HALTED    = 'halted';

    // ----------- End Invoice subscription Statuses -----------------

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
        self::PARTIALLY_PAID,
        self::PAID,
        self::CANCELLED,
        self::EXPIRED,

        self::CREATED,
        self::INITIATED,
        self::GENERATED,
        self::FAILED,
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

    public static function isStatusValid(string $status): bool
    {
        return in_array($status, self::$invoiceStatuses, true);
    }

    public static function checkStatus(string $status)
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
            throw new BadRequestValidationFailureException(
                "Not a valid subscription status: " . $subscriptionStatus);
        }
    }
}
