<?php

namespace RZP\Models\PaymentLink;

class Status
{
    const ACTIVE   = 'active';
    const INACTIVE = 'inactive';

    public static $statuses = [
        self::ACTIVE,
        self::INACTIVE,
    ];

    public static function isValid(string $status): bool
    {
        $key = __CLASS__ . '::' . strtoupper($status);

        return ((defined($key) === true) and (constant($key) === $status));
    }
}
