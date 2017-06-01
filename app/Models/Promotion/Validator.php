<?php

namespace RZP\Models\Promotion;

use Carbon\Carbon;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Schedule\Period;

class Validator extends Base\Validator
{
    const CREDITS_EXPIRY_PERIOD = 'credits_expiry_period';
    const CREDITS_EXPIRY_INTERVAL = 'credits_expiry_interval';

    protected static $createRules = [
        Entity::NAME                      => 'required|string|max:50',
        Entity::AMOUNT                    => 'required|integer|min:1',
        Entity::CREDIT_TYPE               => 'required|in:fee,amount',
        Entity::ITERATIONS                => 'sometimes|integer|min:1',
        Entity::CREDITS_EXPIRABLE         => 'sometimes|boolean',
        self::CREDITS_EXPIRY_INTERVAL     => 'sometimes|integer',
        self::CREDITS_EXPIRY_PERIOD       => 'sometimes|string',
    ];

    protected static $editRules = [
        Entity::NAME                      => 'sometimes|string|max:50',
        Entity::AMOUNT                    => 'sometimes|integer|min:1',
        Entity::CREDIT_TYPE               => 'sometimes|in:fee,amount',
        Entity::ITERATIONS                => 'sometimes|integer|min:1',
        Entity::CREDITS_EXPIRABLE         => 'sometimes|boolean',
        self::CREDITS_EXPIRY_INTERVAL     => 'sometimes|integer',
    ];

    protected static $createValidators = [
        self::CREDITS_EXPIRY_PERIOD,
        self::CREDITS_EXPIRY_INTERVAL,
    ];

    protected static $editValidators = [
        self::CREDITS_EXPIRY_INTERVAL,
    ];

    protected function validateCreditsExpiryPeriod(array $input)
    {
        if (empty($input[Entity::CREDITS_EXPIRABLE]) === true)
        {
            return;
        }

        if (isset($input[self::CREDITS_EXPIRY_PERIOD]) === false)
        {
            throw new  Exception\BadRequestValidationFailureException(
                'The credits expiry period must be sent');
        }

        if (Period::isPeriodValid($input[self::CREDITS_EXPIRY_PERIOD]) === false)
        {
           throw new  Exception\BadRequestValidationFailureException(
                'The credits expiry period is not valid');
        }
    }

    protected function validateCreditsExpiryInterval(array $input)
    {
        if (empty($input[Entity::CREDITS_EXPIRABLE]) === true)
        {
            return;
        }

        if (isset($input[self::CREDITS_EXPIRY_INTERVAL]) === false)
        {
            throw new  Exception\BadRequestValidationFailureException(
                'The credits expiry interval must be sent');
        }
    }
}
