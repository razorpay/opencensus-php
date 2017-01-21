<?php

namespace RZP\Models\Plan\Subscription;

class Status
{
    // Subscription Statuses
    const CREATED           = 'created';
    const ACTIVATED         = 'activated';
    const PROCESSED         = 'processed';
    const ON_HOLD           = 'on_hold';
    const FAILED            = 'failed';
    const CANCELLED         = 'cancelled';

    // Error Statuses
    const AUTH_FAILURE      = 'auth_failure';
    const CAPTURE_FAILURE   = 'capture_failure';

    // These statuses have corresponding timestamps column in subscription
    public static $timestampedStatuses = [
        self::ACTIVATED,
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
