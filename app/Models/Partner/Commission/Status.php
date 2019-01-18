<?php

namespace RZP\Models\Partner\Commission;

class Status
{
    const CREATED = 'created';

    const RECORDED = 'recorded';
    const REVERSED = 'reversed'; // marking a reverse of a recorded commission

    const PROCESSED = 'processed';
    const REFUNDED  = 'refunded';

    /*
     * Allowed next statuses mapping
     */
    const ALLOWED_NEXT_STATUSES_MAPPING = [
        self::CREATED   => [self::RECORDED, self::PROCESSED],
        self::RECORDED  => [self::REVERSED],
        self::REVERSED  => [],
        self::PROCESSED => [self::REFUNDED],
        self::REFUNDED  => [],
    ];

    public static function isValidStateTransition(string $current, string $next)
    {
        return (in_array($next, self::ALLOWED_NEXT_STATUSES_MAPPING[$current],true) === true);
    }
}
