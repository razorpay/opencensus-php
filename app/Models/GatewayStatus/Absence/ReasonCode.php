<?php

namespace RZP\Models\GatewayStatus\Absence;

class ReasonCode
{
    const LOW_SUCCESS_RATE      = 'LOW_SUCCESS_RATE';
    const HIGHER_DECLINES       = 'HIGHER_DECLINES';
    const ISSUER_DOWN           = 'ISSUER_DOWN';
    const SCHEDULED_DOWNTIME    = 'SCHEDULED_DOWNTIME';
    const OTHER                 = 'OTHER';

    protected static $messages = [
        self::LOW_SUCCESS_RATE      => 'Low Success Rate',
        self::HIGHER_DECLINES       => 'Noticed Higher Number of Declines',
        self::ISSUER_DOWN           => 'Issuer bank/ network/ wallet is down',
        self::SCHEDULED_DOWNTIME    => 'Scheduled Downtime',
        self::OTHER                 => 'Un-categorized/other'
    ];

    public static function isValidReasonCode($code)
    {
        return defined('self::' . strtoupper($code));
    }

    const SOURCE_STATUSCAKE  = 'STATUSCAKE';
    const SOURCE_BILLDESK    = 'BILLDESK';
    const SOURCE_OTHER       = 'OTHER';

    protected static $sources = [
        self::SOURCE_STATUSCAKE,
        self::SOURCE_BILLDESK,
        self::SOURCE_OTHER
    ];

    public static function isValidSource($source)
    {
        return in_array(strtoupper($source), self::$sources);
    }

}
