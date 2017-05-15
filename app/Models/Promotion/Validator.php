<?php

namespace RZP\Models\Promotion;

use Carbon\Carbon;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    const CREDITS_EXPIRY_PERIOD = 'credits_expiry_period';
    const CREDITS_EXPIRY_INTERVAL = 'credits_expiry_interval';

    protected static $createRules = [
        Entity::NAME                      => 'required|string|max:50',
        Entity::AMOUNT                    => 'required|integer|min:1',
        Entity::CREDIT_TYPE               => 'required|in:fee,amount',
        Entity::ITERATIONS                => 'sometimes|integer|min:1',
        Entity::CREDITS_EXPIRE            => 'sometimes|boolean',
        self::CREDITS_EXPIRY_INTERVAL     => 'sometimes|integer',
        self::CREDITS_EXPIRY_PERIOD       => 'sometimes|string',
    ];

    protected static $editRules = [
        Entity::NAME                      => 'sometimes|string|max:50',
        Entity::AMOUNT                    => 'required|integer|min:1',
        Entity::CREDIT_TYPE               => 'required|in:fee,amount',
        Entity::ITERATIONS                => 'sometimes|integer|min:1',
    ];

    protected static $scheduleRules = [
        self::CREDITS_EXPIRY_PERIOD,
        self::CREDITS_EXPIRY_INTERVAL,
    ];

    protected static $scheduleValidators = [
        self::CREDITS_EXPIRY_PERIOD,
        self::CREDITS_EXPIRY_INTERVAL,
    ];

    protected function validateCreditsExpiryPeriod(array $input)
    {
        if (isset($input[Entity::CREDITS_EXPIRE]) === false)
        {
            return;
        }

        if (($input[Entity::CREDITS_EXPIRE] === true) or
            (isset($input[Entity::CREDITS_EXPIRY_PERIOD]) === false) or
            Period::isPeriodValid($input[Entity::CREDITS_EXPIRY_PERIOD]) === false)
        {
            throw new Exception\BadRequestException("credits_expire_in required");
        }
    }

    protected function validateCreditsExpiryInterval(array $input)
    {
        if (isset($input[Entity::CREDITS_EXPIRE]) === false)
        {
            return;
        }

        if (($input[Entity::CREDITS_EXPIRE] === true) or
            (isset($input[Entity::CREDITS_EXPIRE_INTERVAL]) === false) or
            (is_int($input[Entity::CREDITS_EXPIRY_INTERVAL]) === false))
        {
            throw new Exception\BadRequestException("credits_expire_in required");
        }
    }
}
