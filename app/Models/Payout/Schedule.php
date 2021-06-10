<?php

namespace RZP\Models\Payout;

use App;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;

class Schedule
{
    const SCHEDULED_AT_MONTHS_ALLOWED = 3;

    /**
     * We currently allow only 4 time slots on the dashboard. These 4 values are automatically aligned with 4 logos
     * corresponding to morning, afternoon, evening and night. Hence, we should always keep them is increasing order.
     *
     * @var array
     */
    protected static $allowedTimeSlots = ['09', '13', '17', '21'];

    public static function getTimeSlotsForScheduledPayouts()
    {
        return self::$allowedTimeSlots;
    }

    /**
     * @param $scheduledAt
     *
     * @throws Exception\BadRequestException
     */
    public static function validateScheduledAt($scheduledAt)
    {
        $app = App::getFacadeRoot();


        if (($app['basicauth']->isProxyAuth() === false) and
            ($app['basicauth']->isVendorPaymentApp() === false) and
            ($app['basicauth']->isPayoutService() === false))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_SCHEDULED_PAYOUT_AUTH_NOT_SUPPORTED);
        }

        self::validateScheduledAtTimeStamp($scheduledAt);

        self::validateScheduledAtTimeSlot($scheduledAt);
    }

    public function updateScheduleTimeToStartOfHour(Entity & $payout)
    {
        $scheduledAt = $payout->getScheduledAt();

        $startOfHour = Carbon::createFromTimestamp($scheduledAt, Timezone::IST)->startOfHour()->getTimestamp();

        $payout->setScheduledAt($startOfHour);
    }

    public static function validateScheduledAtTimeStamp($scheduledAtTime)
    {
        $currentTime = Carbon::now(Timezone::IST)->endOfHour()->getTimestamp();

        $futureTime = Carbon::now(Timezone::IST)->addMonths(self::SCHEDULED_AT_MONTHS_ALLOWED)->getTimestamp();

        if (($scheduledAtTime <= $currentTime) or
            ($scheduledAtTime > $futureTime))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SCHEDULED_PAYOUT_INVALID_TIMESTAMP,
                null,
                [
                    'current_time'      => $currentTime,
                    'max_allowed_time'  => $futureTime,
                    'scheduled_at_time' => $scheduledAtTime
                ]);
        }
    }

    public static function validateCancelOrApproveOrRejectRequest(Entity $payout)
    {
        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        if ($payout->getScheduledAt() <= $currentTime)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SCHEDULED_PAYOUT_CANCEL_REJECT_APPROVE_INVALID_TIMESTAMP,
                null,
                [
                    'schedules_at_time' => $payout->getScheduledAt(),
                    'current_time'      => $currentTime,
                ]);
        }
    }

    protected static function validateScheduledAtTimeSlot($scheduledAtTime)
    {
        $hourOfTheDay = Carbon::createFromTimestamp($scheduledAtTime, Timezone::IST)->startOfHour()->format('H');

        if (in_array($hourOfTheDay, self::$allowedTimeSlots, true) === true)
        {
            return;
        }

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_SCHEDULED_PAYOUT_INVALID_TIME_SLOT,
            null,
            [
                'schedule_time_start_hour'  => $hourOfTheDay,
                'allowed_time_slots'        => self::$allowedTimeSlots,
            ]);
    }
}
