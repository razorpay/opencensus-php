<?php

namespace RZP\Models\Risk;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::PAYMENT_ID    => 'required|public_id',
        Entity::FRAUD_TYPE    => 'required|string|max:30|filled|custom',
        Entity::SOURCE        => 'required|string|max:30|filled|custom',
        Entity::RISK_SCORE    => 'sometimes|numeric',
        Entity::COMMENTS      => 'sometimes|string|max:255|filled',
        Entity::REASON        => 'required|string|max:150',
    ];

    protected static $editRules = [
        Entity::FRAUD_TYPE    => 'sometimes|string|max:30|custom',
        Entity::COMMENTS      => 'sometimes|string|filled',
        Entity::REASON        => 'required|string|max:150',
    ];

    protected static $grievanceFetchInputRules = [
        Entity::ID  => 'required|public_id',
    ];

    protected static $grievancePostInputRules = [
        'email_id'   => 'required|email',
        'contact_no' => 'sometimes|nullable|contact_syntax',
        'name'       => 'sometimes|string|max:50|nullable',
        'comments'   => 'sometimes|string|max:2048',
        'entity_id'  => 'required|public_id',
        'captcha_id' => 'required|string',
        'source'     => 'required|in:customer_email,hosted,txn_confirm_mail',
    ];

    protected function validateFraudType(string $attribute, string $value)
    {
        if (Type::isValidType($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The fraud type for risk logging is invalid',
                Entity::FRAUD_TYPE,
                [Entity::SOURCE => $value]);
        }
    }

    protected function validateSource(string $attribute, string $value)
    {
        if (Source::isValidSource($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The source for risk logging is invalid',
                Entity::SOURCE,
                [Entity::SOURCE => $value]);
        }
    }
}
