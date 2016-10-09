<?php

namespace RZP\Models\Plan;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

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

    protected function validateInterval($attribute, $value)
    {
        if (Interval::isIntervalValid($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid argument for interval passed', null, ['interval' => $value]);
        }
    }
}