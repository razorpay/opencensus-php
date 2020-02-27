<?php

namespace RZP\Models\PayoutLink\Notifications;

class Type
{
    const SEND_LINK                         = 'send_link';
    const PAYOUT_LINK_PROCESSING_SUCCESSFUL = 'payout_link_processing_successful';
    const PAYOUT_LINK_PROCESSING_FAILED     = 'payout_link_processing_failed';

    const VALID_TYPES = [
        self::SEND_LINK,
        self::PAYOUT_LINK_PROCESSING_FAILED,
        self::PAYOUT_LINK_PROCESSING_SUCCESSFUL
    ];

    public static function isValidType(string $type): bool
    {
        return in_array($type, self::VALID_TYPES, true);
    }
}
