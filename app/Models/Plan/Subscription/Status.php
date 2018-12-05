<?php

namespace RZP\Models\Plan\Subscription;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Webhook\Event;

class Status
{
    // ---------------- Subscription statuses ----------------

    /**
     * When a subscription is first created. No charges have been made on this yet.
     */
    const CREATED           = 'created';

    /**
     * The auth transaction is complete and probably upfront amount has also been paid.
     * But, no charge has been made on this yet.
     * This status could be used as something like a trial period by the merchant.
     */
     const AUTHENTICATED    = 'authenticated';

    /**
     * The first charge on the subscription has been made. Basically, the subscription
     * cycle has begun.
     */
    const ACTIVE            = 'active';

    /**
     * When a charge fails and is applicable to be retried more, it's in pending state.
     */
    const PENDING           = 'pending';

    /**
     * When a charge fails and all retries have been exhausted, it's moved to halted state.
     * From this, it can be moved to active, cancelled or expired states.
     */
    const HALTED            = 'halted';

    /**
     * The merchant can cancel a subscription or ask to cancel the subscription after all
     * retries have been exhausted. From cancelled, it cannot be moved back to active again ever.
     */
    const CANCELLED         = 'cancelled';

    /**
     * When the subscription has run its course (all payments done),
     * we set the status to completed.
     */
    const COMPLETED         = 'completed';

    /**
     * When the user does not make an auth transaction by the time
     * the subscription's start_at, we mark it as expired.
     * Auth txn cannot be made after this. The merchant will
     * have to create a new subscription.
     */
    const EXPIRED           = 'expired';

    // ---------------- End subscription statuses ----------------

    // ---------------- Error statuses ----------------

    const AUTH_FAILURE      = 'auth_failure';
    const CAPTURE_FAILURE   = 'capture_failure';

    // ---------------- End error statuses ----------------

    /**
     * These statuses have corresponding timestamps column in subscription
     *
     * @var array
     */
    public static $timestampedStatuses = [
        self::AUTHENTICATED,
        self::CANCELLED,
    ];

    public static $webhookStatuses = [
        self::ACTIVE    => Event::SUBSCRIPTION_ACTIVATED,
        self::PENDING   => Event::SUBSCRIPTION_PENDING,
        self::HALTED    => Event::SUBSCRIPTION_HALTED,
        self::CANCELLED => Event::SUBSCRIPTION_CANCELLED,
        self::COMPLETED => Event::SUBSCRIPTION_COMPLETED,
        // self::EXPIRED   => Event::SUBSCRIPTION_EXPIRED,
    ];

    public static $cardChangeStatuses = [
        self::ACTIVE,
        self::AUTHENTICATED,
        self::PENDING,
        self::HALTED,
    ];

    public static $cronChargeableStatuses = [
        self::AUTHENTICATED,
        self::ACTIVE,
        self::HALTED,
    ];

    public static $manualTestChargeableStatuses = [
        self::AUTHENTICATED,
        self::ACTIVE,
        self::HALTED,
        self::PENDING,
    ];

    public static $invoiceManualChargeableStatuses = [
        self::ACTIVE,
        self::PENDING,
        self::HALTED,
        self::COMPLETED,
        self::CANCELLED
    ];

    public static $terminalStatuses = [
        self::EXPIRED,
        self::CANCELLED,
        self::COMPLETED,
    ];

    public static $latestInvoiceStatuses = [
        self::AUTHENTICATED,
        self::ACTIVE,
        self::PENDING
    ];

    public static $oldInvoiceStatuses = [
        self::ACTIVE,
        self::PENDING,
        self::HALTED,
        self::CANCELLED,
        self::COMPLETED
    ];

    public static $failingStatuses = [
        self::PENDING,
        self::HALTED,
        self::COMPLETED,
    ];

    public static function isStatusValid($status) : bool
    {
        $key = __CLASS__ . '::' . strtoupper($status);

        return ((defined($key) === true) and (constant($key) === $status));
    }

    public static function validateStatus($status)
    {
        if (self::isStatusValid($status) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_INVALID_STATUS,
                Entity::STATUS,
                [
                    'status' => $status
                ]);
        }
    }
}
