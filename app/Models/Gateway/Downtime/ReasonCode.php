<?php

namespace RZP\Models\Gateway\Downtime;

class ReasonCode
{
    const LOW_SUCCESS_RATE      = 'LOW_SUCCESS_RATE';
    const HIGHER_DECLINES       = 'HIGHER_DECLINES';
    const ISSUER_DOWN           = 'ISSUER_DOWN';
    const SCHEDULED_DOWNTIME    = 'SCHEDULED_DOWNTIME';
    const HIGHER_ERRORS         = 'HIGHER_ERRORS';
    const OTHER                 = 'OTHER';

    const SEVERITY_MAP = [
        self::LOW_SUCCESS_RATE   => Severity::MEDIUM,
        self::HIGHER_DECLINES    => Severity::MEDIUM,
        self::HIGHER_ERRORS      => Severity::HIGH,
        self::ISSUER_DOWN        => Severity::HIGH,
        self::SCHEDULED_DOWNTIME => Severity::HIGH,
        self::OTHER              => Severity::LOW
    ];

    protected static $messages = [
        self::LOW_SUCCESS_RATE      => 'Low Success Rate',
        self::HIGHER_DECLINES       => 'Noticed Higher Number of Declines',
        self::ISSUER_DOWN           => 'Issuer bank/ network/ wallet is down',
        self::SCHEDULED_DOWNTIME    => 'Scheduled Downtime',
        self::HIGHER_ERRORS         => 'Gateway gave a higher number of error responses',
        self::OTHER                 => 'Un-categorized/other',
    ];

    public static function isValidReasonCode($code)
    {
        return isset(self::$messages[strtoupper($code)]);
    }

    public static function getSeverity(string $code)
    {
        return ReasonCode::SEVERITY_MAP[strtoupper($code)] ?? null;
    }
}
