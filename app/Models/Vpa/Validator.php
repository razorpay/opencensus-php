<?php

namespace RZP\Models\Vpa;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ADDRESS => 'required|string|custom',
    ];

    public function validateAddress(string $attribute, string $address)
    {
        (new Base\VpaValidator)->validateVpa($address);
    }
}
