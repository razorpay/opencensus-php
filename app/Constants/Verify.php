<?php

namespace RZP\Constants;

class Verify
{
    protected static $unEvenBounday = [
        1 =>  15,            // 15 minute
        2 =>  60,            // 60 minute
    ];

    const DEFAULT_MAXDAYS   = 7;

    const MINUTES_IN_DAY    = 1440;
    const SECONDS_IN_MINUTE = 60;

    const MIN_TIME_BEFORE_VERIFY = 120;  // 2 Minutes

    const SUCCESS       = 'success';
    const ERROR         = 'error';
    const AUTHORIZED    = 'authorized';
    const TIMEOUT       = 'timeout';

    public static function getBoundayInSeconds($daysToAdd = 0)
    {
        $boundary = self::$unEvenBounday;

        foreach (range (1, (self::DEFAULT_MAXDAYS + $daysToAdd)) as $day)
        {
            $boundary[] = $day * self::MINUTES_IN_DAY * self::SECONDS_IN_MINUTE;
        }

        return $boundary;
    }
}
