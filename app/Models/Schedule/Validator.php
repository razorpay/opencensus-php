<?php

namespace RZP\Models\Schedule;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use Carbon\Carbon;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::NAME     => 'sometimes|string|max:50',
        Entity::PERIOD   => 'required|string',
        Entity::TYPE     => 'required|string|in:settlement',
        Entity::INTERVAL => 'sometimes|nullable|integer|max:24',
        Entity::ANCHOR   => 'sometimes|nullable|integer|min:-1|max:30',
        Entity::HOUR     => 'sometimes|integer|min:0|max:23',
        Entity::DELAY    => 'required|integer|min:0|max:30',
        Entity::NEXT_RUN => 'sometimes|integer',
    );

    protected static $editRules = array(
        Entity::NAME     => 'sometimes|string|max:50',
        Entity::INTERVAL => 'sometimes|integer|max:24',
        Entity::ANCHOR   => 'sometimes|integer|min:-1|max:30',
        Entity::HOUR     => 'sometimes|integer|min:0|max:23',
        Entity::DELAY    => 'sometimes|integer|min:0|max:30',
        Entity::NEXT_RUN => 'sometimes|integer',
    );

    protected static $createValidators = array(
        'period',
        'anchor',
        'hour',
    );

    protected static $editValidators = array(
        'anchor',
        'hour',
    );

    protected function validatePeriod($input)
    {
        $period = $input[Entity::PERIOD];

        if (in_array($period, Period::PERIOD_LIST, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SCHEDULE_INVALID_PERIOD);
        }
    }

    protected function validateHour($input)
    {
        if ((isset($input[Entity::PERIOD]) === true) and
            ($input[Entity::PERIOD] === Period::HOURLY))
        {
            if ((isset($input[Entity::HOUR]) === true) and
                (intval($input[Entity::HOUR]) !== 0))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_SCHEDULE_HOURLY_HOUR_NOT_PERMITTED);
            }
        }
    }

    protected function validateAnchor($input)
    {
        if (isset($input[Entity::PERIOD]) === true)
        {
            if ($input[Entity::PERIOD] === Period::WEEKLY)
            {
                $this->validateWeeklyAnchor($input);
            }
            else if (($input[Entity::PERIOD] === Period::DAILY) or
                    ($input[Entity::PERIOD] === Period::HOURLY))
            {
                if (isset($input[Entity::ANCHOR]) === true)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_SCHEDULE_HOURLY_DAILY_ANCHOR_NOT_PERMITTED);
                }
            }
        }
    }

    protected function validateWeeklyAnchor($input)
    {
        if ((isset($input[Entity::ANCHOR]) === true) and
            ((intval($input[Entity::ANCHOR]) < Carbon::MONDAY) or
             (intval($input[Entity::ANCHOR]) > Carbon::FRIDAY)))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SCHEDULE_WEEKEND_ANCHOR_NOT_PERMITTED);
        }
    }
}
