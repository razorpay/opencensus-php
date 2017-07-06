<?php

namespace RZP\Models\Promotion;

use Carbon\Carbon;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Schedule\Period;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME                    => 'required|string|max:50',
        Entity::CREDIT_AMOUNT           => 'required|integer|min:100',
        Entity::CREDIT_TYPE             => 'required|in:amount',
        Entity::ITERATIONS              => 'sometimes|integer|min:1',
        Entity::CREDITS_EXPIRE          => 'sometimes|integer|in:0,1',
        Entity::CREDITS_EXPIRY_INTERVAL => 'required_if:credits_expire,1|integer|min:1',
        Entity::CREDITS_EXPIRY_PERIOD   => 'required_if:credits_expire,1|string|custom',
    ];

    protected static $editRules = [
        Entity::NAME                    => 'sometimes|string|max:50',
        Entity::CREDIT_AMOUNT           => 'sometimes|integer|min:100',
        Entity::CREDIT_TYPE             => 'sometimes|in:amount',
        Entity::ITERATIONS              => 'sometimes|integer|min:1',
        Entity::CREDITS_EXPIRE          => 'sometimes|integer|in:0,1',
        Entity::CREDITS_EXPIRY_INTERVAL => 'required_if:credits_expire,1|integer',
        Entity::CREDITS_EXPIRY_PERIOD   => 'required_if:credits_expire,1|string|custom',
    ];

    protected function validateCreditsExpiryPeriod($attribute, $value)
    {
        $validPeriods = [
            Period::MONTHLY,
            Period::WEEKLY,
        ];

        if (in_array($value, $validPeriods, true) === false)
        {
            throw new  Exception\BadRequestValidationFailureException(
                'The credits expiry period is not valid',
                $attribute,
                $value);
        }
    }
}
