<?php

namespace RZP\Models\Vpa;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ADDRESS => 'required|string|between:3,100|regex:"[a-zA-Z0-9][a-zA-Z0-9\.-]{2,}@[a-zA-Z]+"|custom',
    ];

    protected static $createVirtualVpaRules = [
        Entity::USERNAME => 'required|string|max:40',
        Entity::ADDRESS  => 'required|string|between:3,100|regex:"[a-zA-Z0-9][a-zA-Z0-9\.-]{2,}@[a-zA-Z]+"|custom',
    ];

    public function validateAddress(string $attribute, string $address)
    {
        if (strpos($address, Entity::AROBASE) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Address: ' . $address);
        }

        list($left, $right) = explode(Entity::AROBASE, $address);

        if (strlen($left) < 3)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Handle must be at least three characters: ' . $address);
        }
    }
}
