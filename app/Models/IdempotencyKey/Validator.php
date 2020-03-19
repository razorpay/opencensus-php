<?php

namespace RZP\Models\IdempotencyKey;

use RZP\Base;
use RZP\Constants;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::IDEMPOTENCY_KEY => 'required|alpha_dash_space|filled|min:4|max:36',
        Entity::REQUEST_HASH    => 'required|string|filled|max:1024',
        Entity::SOURCE_TYPE     => 'required|string|custom',
    ];

    protected function validateSourceType($attribute, $value)
    {
        Constants\Entity::validateEntityOrFail($value);
    }
}
