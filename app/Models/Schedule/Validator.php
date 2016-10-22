<?php

namespace RZP\Models\Schedule;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use Carbon\Carbon;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::NAME     => 'sometimes|string|max:50',
        Entity::PERIOD   => 'required|alpha',
        Entity::TYPE     => 'required|string|in:settlement',
        Entity::INTERVAL => 'sometimes|integer|max:24',
        Entity::ANCHOR   => 'sometimes|integer|min:-1|max:30',
        Entity::DELAY    => 'required|integer|min:0|max:30',
        Entity::NEXT_RUN => 'sometimes|integer',
    );

    protected static $editRules = array(
        Entity::NAME     => 'sometimes|string|max:50',
        Entity::INTERVAL => 'sometimes|integer|max:24',
        Entity::ANCHOR   => 'sometimes|integer|min:-1|max:30',
        Entity::DELAY    => 'required|integer|min:0|max:30',
        Entity::NEXT_RUN => 'sometimes|integer',
    );

    protected static $createValidators = array(
        'period',
        'weeklyAnchor',
    );

    protected function validatePeriod($input)
    {
        $period = $input[Entity::PERIOD];

        if (in_array($period, Period::PERIOD_LIST) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SCHEDULE_INVALID_PERIOD);
        }
    }

    protected function validateWeeklyAnchor($input)
    {
        $weekend = [Carbon::SATURDAY, Carbon::SUNDAY];

        if (($input[Entity::PERIOD] == Period::WEEKLY) and
            (isset($input[Entity::ANCHOR]) === true) and
            (in_array($input[Entity::ANCHOR], $weekend) === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SCHEDULE_WEEKEND_ANCHOR_NOT_PERMITTED);
        }
    }
}
