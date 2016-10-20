<?php

namespace RZP\Constants;

class Verify
{
    // ================== Configurations ==================

    /**
     * Verify will run for all the created payments every 2 minutes.
     * All the created payments will be converted to failed in 10 minutes via timeout cron.
     * Hence, at max, verify for the payment (when it is in created state) will be run 5 times.
     */
    protected static $createdStartBoundary = [
        1 => 120,           // 2   Minutes
    ];

    /**
     * For all the payments which are NOT in created state,
     * verify for the payment will be run once for in every boundary bucket.
     */
    protected static $defaultStartBoundary = [
        1 => 900,           // 15  Minutes
        2 => 3600,          // 60  Minutes
        // TODO: Decide on the boundaries.
    ];

    /**
     * We do not run verify if the payment creation is date is greater than 7 days.
     */
    const DEFAULT_MAX_DAYS   = 7;

    /**
     * This is used for naming the redis lock key.
     * It's named as {payment_id}_verify.
     * We do not use the payment_id directly because
     * it's already being used in the core flows of refund and capture.
     */
    const KEY_SUFFIX        = '_verify';

    const SECONDS_IN_DAY    = 86400;

    /**
     * This is the minimum time for which the payment should be in
     * created state, before we run a "created" verify on it.
     */
    const CREATED_MIN_TIME    = 120;  // 2 Minutes

    // TODO: This is present here to ensure backward compatibility and
    // should be removed after the required changes in the cron are made.
    const ALL_MIN_TIME        = 120;  // 2 Minutes

    /**
     * This is the minimum time for which the payment should be in
     * failed state, before we run a "failed/error" verify on it.
     */
    const DEFAULT_MIN_TIME    = 0;    // 0 Minute

    // ================== End Configurations ==================

    // ================== Verify Status ==================

    const SUCCESS       = 'success';
    const ERROR         = 'error';
    const AUTHORIZED    = 'authorized';
    const TIMEOUT       = 'timeout';

    // ================== End Verify Status ==================

    // ================== Verify Results ==================

    const VERIFIED_UNKNOWN  = null;
    const VERIFIED_FAILED   = 0;
    const VERIFIED_SUCCESS  = 1;
    const VERIFIED_ERROR    = 2;

    // ================== End Verify Results ==================

    /*
     * @param string $filter    filter for which boundary has to be returned
     * @param int    $daysToAdd days to be added at end after default days boundary
     *                          while Fetching payments $daysToAdd should be 0
     *                          while Setting VERIFY_BUCKET $daysToAdd should be 1
     *                          as we don't want to verify Payments which are verified after DEFAULT_MAX_DAYS
     *
     * @return array            verify boundary array
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
