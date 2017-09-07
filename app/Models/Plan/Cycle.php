<?php

namespace RZP\Models\Plan;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Models\Schedule\Library;
use RZP\Exception\BadRequestValidationFailureException;

class Cycle
{
    const YEARLY  = 'yearly';
    const MONTHLY = 'monthly';
    const WEEKLY  = 'weekly';
    const DAILY   = 'daily';

    const ONE_YEAR          = 1;
    const MONTHS_IN_YEAR    = Carbon::MONTHS_PER_YEAR;
    const WEEKS_IN_YEAR     = Carbon::WEEKS_PER_YEAR;
    // TODO: This can be 366 too. Fix.
    const DAYS_IN_YEAR      = 365;

    protected static $validPeriods = [
        self::YEARLY,
        self::MONTHLY,
        self::WEEKLY,
        // self::DAILY,
    ];

    protected static $allowedMaxInterval = [
        self::YEARLY  => self::ONE_YEAR * Subscription\Entity::MAX_YEARS_ALLOWED_FOR_SUBSCRIPTION,
        self::MONTHLY => self::MONTHS_IN_YEAR * Subscription\Entity::MAX_YEARS_ALLOWED_FOR_SUBSCRIPTION,
        self::WEEKLY  => self::WEEKS_IN_YEAR * Subscription\Entity::MAX_YEARS_ALLOWED_FOR_SUBSCRIPTION,
        self::DAILY   => self::DAYS_IN_YEAR * Subscription\Entity::MAX_YEARS_ALLOWED_FOR_SUBSCRIPTION,
    ];

    protected static $carbonFunctionMapping = [
        self::YEARLY    => 'years',
        self::MONTHLY   => 'months',
        self::WEEKLY    => 'weeks',
        self::DAILY     => 'days',
    ];

    public static function isPeriodValid(string $period): bool
    {
        return (in_array($period, self::$validPeriods, true) === true);
    }

    public static function validatePeriod($period)
    {
        if (self::isPeriodValid($period) === false)
        {
            throw new BadRequestValidationFailureException(
                'Invalid argument for period passed', Entity::PERIOD, [Entity::PERIOD => $period]);
        }
    }

    /**
     * The maximum allowed total count is basically the maximum
     * allowed interval divided by the interval set for the plan.
     *
     * For example, for a subscription with a plan with period=monthly,
     * the maximum interval allowed is 120 (months).
     * If the plan interval chosen by the merchant is 2, then the total
     * count cannot be more than 60. A total count of 60 for a bi-monthly
     * plan means the subscription is for 10 years.
     *
     * @param Entity $plan
     *
     * @return float
     * @throws BadRequestValidationFailureException
     */
    public static function getMaxAllowedTotalCount(Entity $plan)
    {
        $period = $plan->getPeriod();
        $interval = $plan->getInterval();

        self::validatePeriod($period);

        $maxAllowedInterval = self::getMaxAllowedInterval($period);

        $maxAllowedTotalCount = $maxAllowedInterval / $interval;

        return $maxAllowedTotalCount;
    }

    public static function getMaxAllowedInterval(string $period): int
    {
        self::validatePeriod($period);

        return self::$allowedMaxInterval[$period];
    }

    public static function getTotalCountForOneYear(Entity $plan)
    {
        $maxAllowedTotalCount = self::getMaxAllowedTotalCount($plan);

        $totalCountForOneYear = ($maxAllowedTotalCount / (Subscription\Entity::MAX_YEARS_ALLOWED_FOR_SUBSCRIPTION));

        return $totalCountForOneYear;
    }

    public static function getTotalCountForGivenInterval(Subscription\Entity $subscription): int
    {
        $start = $subscription->getStartAt();
        $end = $subscription->getEndAt();

        $schedule = $subscription->schedule;

        $start = Carbon::createFromTimestamp($start, Timezone::IST);
        $end = Carbon::createFromTimestamp($end, Timezone::IST);

        $nextRun = $start;

        //
        // We are starting with 1 because we would be charging
        // on the start_date also.
        //
        $totalCount = 1;

        while ($nextRun < $end)
        {
            $nextRun = Library::computeFutureRun($schedule, $start, $start, false);

            $start = $nextRun;

            $totalCount++;
        }

        return $totalCount;
    }

    public static function getEndTimeForGivenTotalCount(Subscription\Entity $subscription)
    {
        $schedule = $subscription->schedule;

        $start = $subscription->getStartAt();
        $totalCount = $subscription->getTotalCount();

        $start = Carbon::createFromTimestamp($start, Timezone::IST);

        //
        // We are subtracting one because we would be
        // charging on the start date also.
        // The total count given would be inclusive of the
        // charge made on start date also.
        //
        foreach (range(1, $totalCount - 1) as $i)
        {
            $nextRun = Library::computeFutureRun($schedule, $start, $start, false);

            $start = $nextRun;
        }

        $end = $start->getTimestamp();

        return $end;
    }
}
