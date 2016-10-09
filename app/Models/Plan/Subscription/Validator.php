<?php

namespace RZP\Models\Plan\Subscription;

use RZP\Models\Base;
use RZP\Exception;
use Carbon\Carbon;

class Validator extends Base\Validator
{
    const ONE_YEAR = 86400;
    
    protected static $createRules = [
        Entity::CUSTOMER_ID => 'required|string|size:19',
        Entity::TOKEN_ID    => 'required|string|size:20',
        Entity::QUANTITY    => 'required|integer|max:500',
        Entity::NOTES       => 'sometimes|notes',
        Entity::START_AT    => 'required|integer|custom',
        Entity::END_AT      => 'required|integer',
    ];

    protected static $createValidators = [
          Entity::END_AT,
    ];

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

        $oneYearFromCurrentTime = $currentTime + self::ONE_YEAR;

        if ($value > $oneYearFromCurrentTime)
        {
            throw new Exception\BadRequestValidationFailureException(
                'start_at must be less than one year from now.',
                null,
                [
                    'start_at'  => $value,
                    'one_year'  => $oneYearFromCurrentTime,
                ]);
        }
    }

    protected function validateEndAt($input)
    {
        $startAt = $input[Entity::START_AT];
        $endAt = $input[Entity::END_AT];

        if ($endAt < $startAt)
        {
            throw new Exception\BadRequestValidationFailureException(
                'end_at cannot be greater than start_at.',
                null,
                [
                    'start_at'  => $startAt,
                    'end_at'    => $endAt,
                ]);
        }

        $oneYearFromStartAt = $startAt + self::ONE_YEAR;

        if ($endAt > $oneYearFromStartAt)
        {
            throw new Exception\BadRequestValidationFailureException(
                'end_at should be within one year of start_at',
                null,
                [
                    'start_at'  => $startAt,
                    'end_at'    => $endAt,
                    'one_year'  => $oneYearFromStartAt,
                ]);
        }
    }
}