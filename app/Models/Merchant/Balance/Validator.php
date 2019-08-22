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
        Entity::CHANNEL          => 'sometimes|string|nullable|custom',
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

    protected function validateChannel($attribute, $channel)
    {
        if (Channel::exists($channel) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid account provider:' . $channel);
        }
    }

    /**
     *
     * @param array $input
     *
     * @return bool
     */
    public function isAccountNumberPresentInArray(array & $input) {
        $accountNumber = $input[Entity::ACCOUNT_NUMBER] ?? null;

        if (empty($accountNumber) === true) {
            return false;
        }

        return true;
    }

}

