<?php

namespace RZP\Constants;

class Verify
{
    protected static $createdStartBoundary = [
        1 => 120,           // 2   Minutes
    ];

    protected static $defaultStartBoundary = [
        1 => 900,           // 15  Minutes
        2 => 3600,          // 60  Minutes
    ];

    const DEFAULT_MAX_DAYS   = 7;

    // For locking the payment we will add this suffix to the key(Payment Id)
    const KEY_SUFFIX        = '_verify';

    const SECONDS_IN_DAY    = 86400;

    // Cron should be ran for only payments which are created before a certain time

    // Created payments creation equals or greater 2 minutes
    // filter == created
    const CREATED_MIN_TIME    = 120;  // 2 Minutes

    // TODO: To be removed after crons are changed
    // All payments creation equals or greater 2 minutes
    // filter == all
    const ALL_MIN_TIME        = 120;  // 2 Minutes

    // Failed/Errored payments this time is 0, verify should be ran just after they go in that state
    // filter == failed/error
    const DEFAULT_MIN_TIME    = 0;    // 0 Minute

    const SUCCESS       = 'success';
    const ERROR         = 'error';
    const AUTHORIZED    = 'authorized';
    const TIMEOUT       = 'timeout';

    // VERIFIED columns Result
    const VERIFIED_UNKNOWN  = null;
    const VERIFIED_FAILED   = 0;
    const VERIFIED_SUCCESS  = 1;
    const VERIFIED_ERROR    = 2;
    /*
     * Return the Verify Boundary Array
     * @param string $filter    filter for which boundary has to be returned
     * @param int    $daysToAdd days to be added at end after default days boundary
     *                          while Fetching payments $daysToAdd should be 0
     *                          while Setting VERIFY_BUCKET $daysToAdd should be 1
     *                          as we don't want to verify Payments which are verified after DEFAULT_MAX_DAYS
     * @return array verify boundary array
     *
    */
    public static function getBoundaryInSeconds($filter, $daysToAdd = 0)
    {
        $boundary = self::getStartBoundary($filter);

        if ($filter !== 'created')
        {
            // This will add daily boundaries at the end
            foreach (range (1, (self::DEFAULT_MAX_DAYS + $daysToAdd)) as $day)
            {
                $boundary[] = $day * self::SECONDS_IN_DAY;
            }
        }

        return $boundary;
    }

    /*
     * Return the minimum time before which verify whould be started after payment is created
     * @param string $filter filter for which the minimum time should be returned
     *                       possible values: all, created, error, failure
     * @return int Time(in secs), after which verify cron will pick payments
    */
    public static function getMinimumTimeBeforeVerify($filter)
    {
        $time = self::DEFAULT_MIN_TIME;

        if ($filter === 'all')
        {
            $time = self::ALL_MIN_TIME;
        }
        else if ($filter === 'created')
        {
            $time = self::CREATED_MIN_TIME;
        }

        return $time;
    }

    /*
     * Return the initial uneven boundary for VERIFY_BUCKET
     * @param string $filter filter for which the boundary should be returned
     *                       possible values: all, created, error, failure
     * @return array verify Boundary Array
    */
    public static function getStartBoundary($filter)
    {
        $boundary = self::$defaultStartBoundary;

        if ($filter === 'created')
        {
            $boundary = self::$createdStartBoundary;
        }

        return $boundary;
    }
}
