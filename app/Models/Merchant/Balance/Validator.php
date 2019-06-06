<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::CURRENCY         => 'required|string|in:INR',
        Entity::TYPE             => 'required|string|custom',
        Entity::ACCOUNT_TYPE     => 'filled|string|custom',
        Entity::ACCOUNT_PROVIDER => 'sometimes|string|nullable|custom',
    ];

    protected function validateType($attribute, $type)
    {
        if (Type::exists($type) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid channel name: ' . $type);
        }
    }

    protected function validateAccountType($attribute, $accType)
    {
        if (AccountType::exists($accType) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid account type:' . $accType);
        }
    }

    protected function validateAccountProvider($attribute, $accProvider)
    {
        if (AccountProvider::exists($accProvider) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid account provider:' . $accProvider);
        }
    }
}

