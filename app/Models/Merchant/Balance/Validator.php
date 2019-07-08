<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Base;
use RZP\Exception;
use RZP\Models\BankingAccount\Channel;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::CURRENCY            => 'required|string|in:INR',
        Entity::TYPE                => 'required|string|custom',
        Entity::ACCOUNT_NUMBER      => 'string|nullable',
        Entity::ACCOUNT_PROVIDER    => 'sometimes|string|nullable',
        Entity::ACCOUNT_TYPE        => 'string|nullable'
    ];

    protected function validateType($attribute, $type)
    {
        if (Type::exists($type) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid type name: ' . $type);
        }
    }

    protected function validateAccountProvider($attribute, $provider)
    {
        if (Channel::validateChannel($provider) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid account provider name: ' . $provider);
        }
    }
}

