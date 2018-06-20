<?php

namespace RZP\Models\State;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME       => 'required|string|max:150|custom',
        Entity::CREATED_AT => 'sometimes|integer',
    ];

    public function validateName(string $attr, string $state)
    {
        Name::validate($state);
    }
}
