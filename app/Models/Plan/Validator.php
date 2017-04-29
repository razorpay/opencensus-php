<?php

namespace RZP\Models\Plan;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::AMOUNT          => 'required|integer|min:100',
        Entity::CURRENCY        => 'required|string|size:3|in:INR',
        Entity::INTERVAL        => 'required|integer|min:1|max:365',
        Entity::PERIOD          => 'required|string|custom',
        // Entity::PERIOD          => 'required|string',
        // Entity::FREQUENCY       => 'required|array',
        Entity::NAME            => 'required|string|min:1|max:256',
        Entity::NOTES           => 'sometimes|notes'
    ];

    protected static $createValidators = [
        Entity::INTERVAL
    ];

    protected function validatePeriod($attribute, $value)
    {
        if (Cycle::isPeriodValid($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid argument for period passed', null, ['period' => $value]);
        }
    }

    protected function validateInterval($input)
    {
        $period = $input[Entity::PERIOD];
        $interval = $input[Entity::INTERVAL];

        $maxAllowedInterval = Cycle::getMaxAllowedInterval($period);

        if ($interval > $maxAllowedInterval)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Exceeds the maximum interval allowed for the given interval',
                null,
                [
                    'interval'      => $interval,
                    'period'        => $period,
                    'max_allowed'   => $maxAllowedInterval
                ]);
        }
    }
}
