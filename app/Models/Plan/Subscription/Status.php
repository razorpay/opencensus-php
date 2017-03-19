<?php

namespace RZP\Models\Plan\Subscription;

class Status
{
    // ---------------- Subscription statuses ----------------

    // When a subscription is first created. No charges have been made on this yet.
    const CREATED           = 'created';

    // The auth transaction is complete and probably upfront amount has also been paid.
    // But, no charge has been made on this yet.
    const AUTHENTICATED     = 'authenticated';

    // The auth transaction is complete and probably upfront amount has also been paid.
    // But, no charge has been made on this yet.
    // const ACTIVATED         = 'activated';

    // The last charge which was made on the subscription was successfully captured.
    const PROCESSED         = 'processed';
    const ON_HOLD           = 'on_hold';
    const FAILED            = 'failed';
    const CANCELLED         = 'cancelled';

    // ---------------- End subscription statuses ----------------

    // Error Statuses
    const AUTH_FAILURE      = 'auth_failure';
    const CAPTURE_FAILURE   = 'capture_failure';

    // These statuses have corresponding timestamps column in subscription
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
