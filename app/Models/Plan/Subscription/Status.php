<?php

namespace RZP\Models\Plan\Subscription;

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
     const AUTHENTICATED     = 'authenticated';

    /**
     * The first charge on the subscription has been made. Basically, the subscription
     * cycle has begun.
     */
    const ACTIVE            = 'active';

    /**
     * When a charge fails and is applicable to be retried more, it's in overdue state.
     */
    const OVERDUE           = 'overdue';

    /**
     * When a charge fails and all retries have been exhausted, it's moved to on_hold state.
     * From this, it can be moved to active, cancelled or expired states.
     */
    const ON_HOLD           = 'on_hold';

    /**
     * The merchant can cancel a subscription or ask to cancel the subscription after all
     * retries have been exhausted. From cancelled, it cannot be moved back to active again ever.
     */
    const CANCELLED         = 'cancelled';

    const FAILED            = 'failed';

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
