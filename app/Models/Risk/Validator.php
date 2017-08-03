<?php

namespace RZP\Models\Risk;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::PAYMENT_ID    => 'required|string|size:14',
        Entity::MERCHANT_ID   => 'sometimes|string|size:14',
        Entity::FRAUD_TYPE    => 'required|string|max:30|filled|custom',
        Entity::SOURCE        => 'required|string|max:30|filled|custom',
        Entity::RISK_SCORE    => 'sometimes|numeric',
        Entity::COMMENTS      => 'sometimes|string|max:255|filled', # soft validation
        Entity::REASON        => 'required|string|max:150',
    ];

    protected static $editRules = [
        Entity::PAYMENT_ID    => 'sometimes|string|size:14',
        Entity::MERCHANT_ID   => 'sometimes|string|size:14',
        Entity::FRAUD_TYPE    => 'sometimes|string|max:30|custom',
        Entity::SOURCE        => 'sometimes|string|max:30|custom',
        Entity::RISK_SCORE    => 'sometimes|numeric',
        Entity::COMMENTS      => 'sometimes|string|filled',
        Entity::REASON        => 'required|string|max:150',
    ];

    protected function validateFraudType(string $attribute, string $value)
    {
        $types = Type::getAllTypes();

        if (in_array($value, $types, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The fraud type for risk logging is invalid',
                Entity::FRAUD_TYPE, $value);
        }
    }

    protected function validateSource(string $attribute, string $value)
    {
        $sources = Source::getAllSources();

        if (in_array($value, $sources, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The source for risk logging is invalid',
                Entity::SOURCE, $value);
        }
    }
}
