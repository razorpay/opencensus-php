<?php

namespace RZP\Models\Payout\Notifications;

class Type
{
    const PAYOUT_AUTO_REJECTED  = 'payout_auto_rejected';
    const PAYOUT_FAILED         = 'payout_failed';

    const VALID_TYPES = [
        self::PAYOUT_AUTO_REJECTED,
        self::PAYOUT_FAILED
    ];

    public static function isValidType(string $type): bool
    {
        return in_array($type, self::VALID_TYPES, true);
    }
}
