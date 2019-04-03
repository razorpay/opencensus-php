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
}
