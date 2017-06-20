<?php

namespace RZP\Models\Promotion;

use Carbon\Carbon;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Schedule\Period;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME                    => 'required|string|max:50',
        Entity::AMOUNT                  => 'required|integer|min:1',
        Entity::CREDIT_TYPE             => 'required|in:amount',
        Entity::ITERATIONS              => 'sometimes|integer|min:1',
        Entity::CREDITS_EXPIRABLE       => 'sometimes|integer|in:0,1',
        Entity::CREDITS_EXPIRY_INTERVAL => 'required_only_if:credits_expirable,1|integer',
        Entity::CREDITS_EXPIRY_PERIOD   => 'required_only_if:credits_expirable,1|string|custom',
    ];

    protected static $editRules = [
        Entity::NAME                    => 'sometimes|string|max:50',
        Entity::AMOUNT                  => 'sometimes|integer|min:1',
        Entity::CREDIT_TYPE             => 'sometimes|in:fee,amount',
        Entity::ITERATIONS              => 'sometimes|integer|min:1',
        Entity::CREDITS_EXPIRABLE       => 'sometimes|integer|in:0,1',
        Entity::CREDITS_EXPIRY_INTERVAL => 'required_only_if:credits_expirable,1|integer',
    ];

    protected function validateCreditsExpiryPeriod($attribute, $value)
    {
        if (Period::isPeriodValid($value) === false)
        {
            throw new  Exception\BadRequestValidationFailureException(
                'The credits expiry period is not valid',
                $attribute,
                $value);
        }
    }
}
