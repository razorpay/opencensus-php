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
        Entity::BALANCE             => 'filled',
        Entity::ACCOUNT_NUMBER      => 'filled',
        Entity::ACCOUNT_PROVIDER    => 'filled|custom',
        Entity::ACCOUNT_TYPE        => 'filled|string|in:Direct,Shared'
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

