<?php

namespace RZP\Models\GatewayStatus\Absence;

class Source
{
    const SOURCE_STATUSCAKE  = 'STATUSCAKE';
    const SOURCE_BILLDESK    = 'BILLDESK';
    const SOURCE_BANK        = 'BANK';
    const SOURCE_OTHER       = 'OTHER';

    protected static $sources = [
        self::SOURCE_STATUSCAKE,
        self::SOURCE_BILLDESK,
        self::SOURCE_BANK,
        self::SOURCE_OTHER
    ];

    public static function isValidSource($source)
    {
        return in_array(strtoupper($source), self::$sources, true);
    }

}