<?php

namespace RZP\Models\Plan;

class Interval
{
    const YEAR  = 'year';
    const MONTH = 'month';
    const WEEK  = 'week';
    const DAY   = 'day';

    public static function isIntervalValid($interval)
    {
        return (defined(Interval::class . '::' . strtoupper($interval)));
    }
}