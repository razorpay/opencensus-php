<?php

namespace RZP\Models\PaymentLink;

class StatusReason
{
    const EXPIRED           = 'expired';
    const DEACTIVATED       = 'deactivated';
    const COMPLETED         = 'completed';

    public static $statusReasons = [
        self::EXPIRED,
        self::DEACTIVATED,
        self::COMPLETED,
    ];

    public static function exists(string $status): bool
    {
        return defined(get_class() . '::' . strtoupper($status));
    }
}
