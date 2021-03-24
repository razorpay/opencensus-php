<?php

namespace RZP\Models\Gateway\Downtime;

class Severity
{
    const LOW    = 'low';
    const MEDIUM = 'medium';
    const HIGH   = 'high';

    const PRECEDENCE = [
        self::HIGH   => 0,
        self::MEDIUM => 1,
        self::LOW    => 2,
    ];

    const PRECEDENCE_SOURCE = [
        Source::DOWNTIME_SERVICE => 0,
        Source::DOWNTIME_V2 => 1,
        Source::VAJRA => 2,
        Source::BANK => 3,
        Source::BILLDESK => 4,
        Source::INTERNAL => 5,
        Source::OTHER => 6,
        Source::STATUSCAKE => 7,
        Source::DOPPLER => 8,
        Source::DUMMY => 9,
    ];
}
