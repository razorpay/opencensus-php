<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Base;
use RZP\Exception;
use RZP\Models\BankingAccount\Channel;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::CURRENCY         => 'required|string|in:INR',
        Entity::TYPE             => 'required|string|custom',
        Entity::ACCOUNT_TYPE     => 'filled|string|custom',
        Entity::CHANNEL          => 'sometimes|string|nullable|custom',
    ];

    protected function validateType($attribute, $type)
    {
        if (Type::exists($type) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid type name: ' . $type);
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

    protected function validateChannel($attribute, $channel)
    {
        if (Channel::validateChannel($channel) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid account provider:' . $channel);
        }
    }
}

