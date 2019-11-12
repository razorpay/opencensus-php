<?php

namespace RZP\Models\Vpa;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ADDRESS => 'required|string|between:3,100|regex:"[a-zA-Z0-9]{1,}@[a-zA-Z]+"|custom',
    ];

    public function validateAddress(string $attribute, string $address)
    {
        if (strpos($address, Entity::AROBASE) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Address: ' . $address);
        }

        list($left, $right) = explode(Entity::AROBASE, $address);

        if (strlen($left) < 1)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Handle must be at least 1 character: ' . $address);
        }
    }
}
