<?php

namespace RZP\Models\PaymentLink;

class StatusReason
{
    const EXPIRED     = 'expired';
    const DEACTIVATED = 'deactivated';
    const COMPLETED   = 'completed';

    public static $statusReasons = [
        self::EXPIRED,
        self::DEACTIVATED,
        self::COMPLETED,
    ];

    public static function isValid(string $status): bool
    {
        $key = __CLASS__ . '::' . strtoupper($status);

        return ((defined($key) === true) and (constant($key) === $status));
    }
}
