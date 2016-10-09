<?php

namespace RZP\Models\Plan;

use RZP\Models\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::AMOUNT          => 'required|integer|min:1',
        Entity::CURRENCY        => 'required|string|size:3|in:INR',
        Entity::INTERVAL        => 'required|string|custom',
        Entity::INTERVAL_COUNT  => 'required|integer|min:1|max:365',
        Entity::NAME            => 'required|string|min:1|max:256',
        Entity::NOTES           => 'sometimes|notes'
    ];

    protected static $createValidators = [
        Entity::INTERVAL_COUNT
    ];

    protected function validateInterval($attribute, $value)
    {
        if (Interval::isIntervalValid($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid argument for interval passed', null, ['interval' => $value]);
        }
    }

    protected function validateIntervalCount($input)
    {
        $interval = $input[Entity::INTERVAL];
        $intervalCount = $input[Entity::INTERVAL_COUNT];

        $maxAllowedIntervalCount = Interval::getMaxAllowedIntervalCount($interval);

        if ($intervalCount > $maxAllowedIntervalCount)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Exceeds the maximum interval count allowed for the given interval',
                null,
                [
                    'interval'          => $interval,
                    'interval_count'    => $intervalCount,
                    'max_allowed'       => $maxAllowedIntervalCount
                ]
            );
        }
    }
}