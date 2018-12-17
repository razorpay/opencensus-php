<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::BALANCE  => 'required|integer|in:0',
        Entity::CURRENCY => 'required|string|in:INR',
        Entity::TYPE     => 'required|string|custom',
    ];

    protected function validateType($attribute, $type)
    {
        if (Type::exists($type) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid channel name: ' . $type);
        }
    }
}

