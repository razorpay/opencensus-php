<?php

namespace RZP\Models\Item;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ACTIVE      => 'filled|boolean',
            Entity::TYPE        => 'filled|custom',
            Entity::MERCHANT_ID => 'filled|alpha_num|size:14'
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
        ],
        AuthType::PROXY_AUTH => [
            Entity::TYPE,
        ],
        AuthType::PRIVATE_AUTH => [
            Entity::ACTIVE,
        ],
    ];

    protected function validateType($attribute, $value)
    {
        Type::checkType($value);
    }
}
