<?php

namespace RZP\Models\PaymentLink;

class Status
{
    const ACTIVE        = 'active';
    const INACTIVE      = 'inactive';

    public static $statuses = [
        self::ACTIVE,
        self::INACTIVE,
    ];

    public static function exists(string $status): bool
    {
        return defined(get_class() . '::' . strtoupper($status));
    }
}
