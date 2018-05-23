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

    public static function isValid(string $statusReason): bool
    {
        $key = __CLASS__ . '::' . strtoupper($statusReason);

        return ((defined($key) === true) and (constant($key) === $statusReason));
    }
}
