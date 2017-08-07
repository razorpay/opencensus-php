<?php

namespace RZP\Models\Dispute;

class Status
{
    const OPEN              = 'open';
    const UNDER_REVIEW      = 'under_review';
    const LOST              = 'lost';
    const WON               = 'won';

    public static function exists($status)
    {
        return defined(get_class() . '::' . strtoupper($status));
    }
}
