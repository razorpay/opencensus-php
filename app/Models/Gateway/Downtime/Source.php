<?php

namespace RZP\Models\Gateway\Downtime;

class Source
{
    const STATUSCAKE  = 'STATUSCAKE';
    const BILLDESK    = 'BILLDESK';
    const BANK        = 'BANK';
    const OTHER       = 'OTHER';

    protected static $sources = [
        self::STATUSCAKE,
        self::BILLDESK,
        self::BANK,
        self::OTHER
    ];

    public static function isValidSource($source)
    {
        return in_array(strtoupper($source), self::$sources, true);
    }

}