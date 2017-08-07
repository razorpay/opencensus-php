<?php

namespace RZP\Models\Risk;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::PAYMENT_ID    => 'required|public_id',
        Entity::MERCHANT_ID   => 'sometimes|alpha_num|size:14',
        Entity::FRAUD_TYPE    => 'required|string|max:30|filled|custom',
        Entity::SOURCE        => 'required|string|max:30|filled|custom',
        Entity::RISK_SCORE    => 'sometimes|numeric',
        Entity::COMMENTS      => 'sometimes|string|max:255|filled', # soft validation
        Entity::REASON        => 'required|string|max:150',
    ];

    protected static $editRules = [
        Entity::FRAUD_TYPE    => 'sometimes|string|max:30|custom',
        Entity::SOURCE        => 'sometimes|string|max:30|custom',
        Entity::RISK_SCORE    => 'sometimes|numeric',
        Entity::COMMENTS      => 'sometimes|string|filled',
        Entity::REASON        => 'required|string|max:150',
    ];

    protected function validateFraudType(string $attribute, string $value)
    {
        if (Type::isValidType($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The fraud type for risk logging is invalid',
                Entity::FRAUD_TYPE, $value);
        }
    }

    protected function validateSource(string $attribute, string $value)
    {
        if (Source::isValidSource($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The source for risk logging is invalid',
                Entity::SOURCE, $value);
        }
    }
}
