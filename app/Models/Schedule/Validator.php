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
        Entity::INTERVAL => 'sometimes|nullable|integer|max:24',
        // For yearly periods, anchor can be december 31st (1231).
        Entity::ANCHOR   => 'sometimes|nullable|integer|min:-1|max:1231',
        Entity::HOUR     => 'sometimes|integer|min:0|max:23',
        Entity::DELAY    => 'sometimes|integer|min:0|max:30',
    );

    protected static $editRules = array(
        Entity::NAME     => 'sometimes|string|max:50',
        Entity::INTERVAL => 'sometimes|integer|max:24',
        // For yearly periods, anchor can be december 31st (1231).
        Entity::ANCHOR   => 'sometimes|integer|min:-1|max:1231',
        Entity::HOUR     => 'sometimes|integer|min:0|max:23',
        Entity::DELAY    => 'sometimes|integer|min:0|max:30',
    );

    protected static $createValidators = array(
        'period',
        'anchor',
        'hour',
        'interval',
    );

    protected static $editValidators = array(
        'anchor',
        'hour',
        'interval',
    );

    protected function validatePeriod($input)
    {
        $period = $input[Entity::PERIOD];

        Period::validatePeriod($period);
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

    protected function validateInterval($input)
    {
        if ((isset($input[Entity::PERIOD]) === true) and
            ($input[Entity::PERIOD] === Period::HOURLY))
        {
            if (isset($input[Entity::INTERVAL]) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_SCHEDULE_HOURLY_WITHOUT_INTERVAL);
            }
        }
    }

    protected function validateAnchor($input)
    {
        if (isset($input[Entity::PERIOD]) === false)
        {
            return;
        }

        if (isset($input[Entity::ANCHOR]) === true)
        {
            if (Period::isPeriodUnAnchored($input[Entity::PERIOD]) === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_SCHEDULE_ANCHOR_NOT_PERMITTED,
                    Entity::ANCHOR,
                    $input);
            }
        }
    }
}
