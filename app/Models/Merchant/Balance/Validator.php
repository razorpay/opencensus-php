<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Exception;

class Validator
{
    protected static $createRules = [
        Entity::ID       => 'sometimes|alpha_num|size:14',
        Entity::BALANCE  => 'required',
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

