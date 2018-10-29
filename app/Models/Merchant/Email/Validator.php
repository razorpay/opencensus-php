<?php

namespace RZP\Models\Merchant\Email;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::TYPE  => 'required|string|max:255|custom',
        Entity::EMAIL => 'required|email|max:255',
    ];

    public function validateType(string $attribute, string $value)
    {
        if (Type::exists($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Email type is invalid: ' . $value);
        }
    }
}
