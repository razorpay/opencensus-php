<?php

namespace RZP\Models\Dispute;

class Status
{
    const OPEN         = 'open';
    const UNDER_REVIEW = 'under_review';
    const LOST         = 'lost';
    const WON          = 'won';
    const CLOSED       = 'closed';

    protected static $closedStatuses = [
        self::WON,
        self::LOST,
        self::CLOSED,
    ];

    public static function exists(string $status): bool
    {
        return defined(get_class() . '::' . strtoupper($status));
    }

    public static function getClosedStatuses(): array
    {
        return self::$closedStatuses;
    }
}
