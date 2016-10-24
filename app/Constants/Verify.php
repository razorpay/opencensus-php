<?php

namespace RZP\Constants;

use RZP\Exception;

class Verify
{
    // ================== Configurations ==================

    /**
     * Verify will run for all the created payments every 2 minutes.
     * All the created payments will be converted to failed in 10 minutes via timeout cron.
     * Hence, at max, verify for the payment (when it is in created state) will be run 5 times.
     */
    protected static $createdStartBoundary = [
        120,           // 2 Minutes
    ];

    /**
     * For all the payments which are in failed state,
     * verify for the payment will be run once for in every boundary bucket.
     */
    protected static $failureStartBoundary = [
        15,            // 15 Minutes
        60,            // 60 Minutes
        1440,          // 1 Day
        2880,          // 2 Day
        4320,          // 3 Day
        5760,          // 4 Day
        7200,          // 5 Day
        8640,          // 6 Day
        10080,         // 7 Day
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
    const KEY_SUFFIX = '_verify';

    const SECONDS_IN_DAY = 86400;

    /**
     * This is the minimum time for which the payment should be in
     * created state, before we run a "created" verify on it.
     */
    const CREATED_MIN_TIME = 120;  // 2 Minutes

    // TODO: This is present here to ensure backward compatibility and
    // should be removed after the required changes in the cron are made.
    const FAILURE_MIN_TIME = 120;  // 2 Minutes

    /**
     * This is the minimum time for which the payment should be in
     * failed state, before we run a "failed/error" verify on it.
     */
    const ERRORED_MIN_TIME = 0; // 0 Minute

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

    // ================== Verify Filter ==================

    const PAYMENTS_CREATED  = 'payments_created';
    const PAYMENTS_FAILED   = 'payments_failed';
    const VERIFY_ERROR      = 'verify_error';

    // ================== End Verify Filter ==================

    /**
     * @param string $filter filter for which boundary has to be returned
     * @return array verify boundary array
     * @throws Exception\LogicException
     */
    public static function getBoundaryInSeconds($filter)
    {
        switch($filter)
        {
            // TODO: remove 'created', 'failure', 'error' and 'all' filter
            case 'created':
            case self::PAYMENTS_CREATED:
                $boundaries = self::$createdStartBoundary;
                break;

            case 'failure':
            case 'error':
            case self::VERIFY_ERROR:
            case 'all':
            case self::PAYMENTS_FAILED:

                $boundaries = self::$failureStartBoundary;

                // Converts Minutes to Seconds
                $boundaries = array_map(function($boundary)
                {
                    return $boundary * 60;
                }, $boundaries);

                break;

            default:
                throw new Exception\LogicException('Unknown filter provided.', null, ['filter' => $filter]);
        }

        return $boundaries;
    }
}
