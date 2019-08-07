<?php

namespace RZP\Models\Merchant\Email;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::TYPE   => 'required|string|max:255|custom',
        Entity::EMAIL  => 'required|email|max:255',
        Entity::PHONE  => 'sometimes|string|nullable',
        Entity::POLICY => 'sometimes|string|nullable',
        Entity::URL    => 'sometimes|string|nullable',
    ];

    protected static $editRules = [
        Entity::TYPE   => 'required|string|max:255|custom',
        Entity::EMAIL  => 'sometimes|email|max:255',
        Entity::PHONE  => 'sometimes|string|nullable',
        Entity::POLICY => 'sometimes|string|nullable',
        Entity::URL    => 'sometimes|string|nullable',
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
