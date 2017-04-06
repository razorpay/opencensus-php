<?php

namespace RZP\Models\Plan\Subscription;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception;
use Carbon\Carbon;

class Validator extends Base\Validator
{
    /**
     * Number of years allowed for a subscription.
     *
     * TODO: We may have to take into consideration leap years also.
     */
    const MAX_YEARS_ALLOWED_FOR_SUBSCRIPTION = 1;

    const SECONDS_IN_ONE_YEAR = 31536000;

    protected static $createRules = [
        Entity::CUSTOMER_ID     => 'required|string|size:19',
        // Entity::TOKEN_ID     => 'required|string|size:20',
        Entity::QUANTITY        => 'required|integer|max:500',
        Entity::NOTES           => 'sometimes|notes',
        Entity::TOTAL_COUNT     => 'required_without:end_at|integer|max:365',
        Entity::START_AT        => 'sometimes|integer|custom',
        Entity::END_AT          => 'required_without:total_count|integer',
        Entity::UPFRONT_AMOUNT  => 'sometimes|integer|max:50000000',
    ];

    protected static $createValidators = [
        Entity::TOTAL_COUNT,
        Entity::END_AT,
    ];

    public function validateEndAtAfterGenerating()
    {
        $subscription = $this->entity;

        $startAt = $subscription->getStartAt();
        $endAt = $subscription->getEndAt();

        $this->validateEndAtWithStartAt($startAt, $endAt);
    }

    public function validateStartAtForAuthTransaction()
    {
        $subscription = $this->entity;

        $startAt = $subscription->getStartAt();

        if ($startAt === null)
        {
            return;
        }

        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        if ($startAt < $currentTime)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_CURRENT_TIME_PAST_START_TIME,
                null,
                [
                    'start_at' => $startAt,
                    'current_time' => $currentTime,
                    'subscription_id' => $subscription->getId(),
                ]);
        }
    }

    protected function validateEndAtWithStartAt(int $startAt, int $endAt)
    {
        if ($endAt < $startAt)
        {
            throw new Exception\BadRequestValidationFailureException(
                'end_at cannot be lesser than start_at.',
                null,
                [
                    'start_at'  => $startAt,
                    'end_at'    => $endAt,
                ]);
        }

        $maxSecondsFromStartAt = $startAt + (self::MAX_YEARS_ALLOWED_FOR_SUBSCRIPTION * self::SECONDS_IN_ONE_YEAR);

        if ($endAt > $maxSecondsFromStartAt)
        {
            throw new Exception\BadRequestValidationFailureException(
                'end_at should be within ' . self::MAX_YEARS_ALLOWED_FOR_SUBSCRIPTION . ' years of start_at.',
                null,
                [
                    'start_at'  => $startAt,
                    'end_at'    => $endAt,
                    'max_years' => self::MAX_YEARS_ALLOWED_FOR_SUBSCRIPTION,
                    'seconds'   => $maxSecondsFromStartAt,
                ]);
        }
    }

    protected function validateEndAt($input)
    {
        //
        // This is possible when total_count is sent in the input.
        //
        if (empty($input[Entity::END_AT]) === true)
        {
            return;
        }

        if (empty($input[Entity::TOTAL_COUNT]) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_END_AT_AND_TOTAL_COUNT_SENT,
                null,
                [
                    'end_at'        => $input[Entity::END_AT],
                    'total_count'   => $input[Entity::TOTAL_COUNT],
                ]);
        }

        $startAt = $input[Entity::START_AT];
        $endAt = $input[Entity::END_AT];

        $this->validateEndAtWithStartAt($startAt, $endAt);
    }

    protected function validateStartAt($attribute, $value)
    {
        $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

        if ($value < $currentTime)
        {
            throw new Exception\BadRequestValidationFailureException(
                'start_at cannot be lesser than the current time.',
                null,
                [
                    'start_at'      => $value,
                    'current_time'  => $currentTime,
                ]);
        }

        $maxSecondsFromCurrentTime = $currentTime + (self::MAX_YEARS_ALLOWED_FOR_SUBSCRIPTION * self::SECONDS_IN_ONE_YEAR);

        if ($value > $maxSecondsFromCurrentTime)
        {
            throw new Exception\BadRequestValidationFailureException(
                'start_at must be less than one year from now.',
                null,
                [
                    'start_at'  => $value,
                    'max_year'  => self::MAX_YEARS_ALLOWED_FOR_SUBSCRIPTION,
                    'seconds'   => $maxSecondsFromCurrentTime,
                ]);
        }
    }

    protected function validateTotalCount($input)
    {
        //
        // This is possible when end_at is sent in the input.
        //
        if (empty($input[Entity::TOTAL_COUNT]) === true)
        {
            return;
        }

        if (empty($input[Entity::END_AT]) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_END_AT_AND_TOTAL_COUNT_SENT,
                null,
                [
                    'end_at'        => $input[Entity::END_AT],
                    'total_count'   => $input[Entity::TOTAL_COUNT],
                ]);
        }

        // TODO: Add more validations around the maximum value of
        // total_count depending on the interval and period of the plan.
    }
}
