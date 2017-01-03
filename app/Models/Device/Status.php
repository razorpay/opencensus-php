<?php

namespace RZP\Models\Device;

class Status
{
    const CREATED       = 'created';
    const VERIFIED      = 'verified';
    const REGISTERED    = 'registered';

    // These statuses have corresponding timestamps column in invoice
    public static $timestampedStatuses = [
        self::VERIFIED,
        self::REGISTERED,
    ];

    public static function isStatusValid($status)
    {
        return (defined(__CLASS__ . '::' . strtoupper($status)));
    }

    public static function checkStatus($status)
    {
        if (self::isStatusValid($status) === false)
        {
            throw new \InvalidArgumentException('Not a valid status: ' . $status);
        }
    }
}
