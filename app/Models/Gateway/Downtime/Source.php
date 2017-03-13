<?php

namespace RZP\Models\Gateway\Downtime;

class Source
{
    const STATUSCAKE  = 'STATUSCAKE';
    const BILLDESK    = 'BILLDESK';
    const BANK        = 'BANK';
    const OTHER       = 'OTHER';

    protected static $sources = [
        Source::STATUSCAKE,
        Source::BILLDESK,
        Source::BANK,
        Source::OTHER
    ];

    public static function isValidSource($source)
    {
        return in_array(strtoupper($source), self::$sources, true);
    }

}