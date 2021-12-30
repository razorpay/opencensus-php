<?php

namespace RZP\Models\Payout\Notifications;

class Type
{
    const PAYOUT_AUTO_REJECTED                   = 'payout_auto_rejected';
    const PAYOUT_FAILED                          = 'payout_failed';
    const PAYOUT_PROCESSED_CONTACT_COMMUNICATION = 'contact_communication_payout_processed';

    const VALID_TYPES = [
        self::PAYOUT_AUTO_REJECTED,
        self::PAYOUT_FAILED,
        self::PAYOUT_PROCESSED_CONTACT_COMMUNICATION
    ];

    public static function isValidType(string $type): bool
    {
        return in_array($type, self::VALID_TYPES, true);
    }
}
