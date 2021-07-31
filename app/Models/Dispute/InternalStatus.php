<?php

namespace RZP\Models\Dispute;


class InternalStatus
{
    const OPEN         = 'open';
    const CONTESTED    = 'contested';
    const REPRESENTED  = 'represented';
    const LOST         = 'lost';
    const WON          = 'won';
    const CLOSED       = 'closed';

    protected static $closedStatuses = [
        self::WON,
        self::LOST,
        self::CLOSED,
    ];

    protected static $openStatuses = [
        self::OPEN,
        self::CONTESTED,
        self::REPRESENTED,
    ];


    public static function exists(string $status): bool
    {
        return defined(get_class() . '::' . strtoupper($status));
    }
}
