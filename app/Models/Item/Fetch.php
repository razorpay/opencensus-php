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
            Entity::MERCHANT_ID => 'filled|alpha_num|size:14',
            self::EXPAND_EACH   => 'filled|string|in:tax',
            EsRepository::QUERY => 'sometimes|string|min:1|max:100',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
        ],
        AuthType::PROXY_AUTH => [
            Entity::TYPE,
            self::EXPAND_EACH,
            EsRepository::QUERY,
        ],
        AuthType::PRIVATE_AUTH => [
            Entity::ACTIVE,
        ],
    ];

    const ES_FIELDS = [
        EsRepository::QUERY,
    ];

    const COMMON_FIELDS = [
        Entity::MERCHANT_ID,
        Entity::ACTIVE,
        Entity::TYPE,
    ];

    protected function validateType($attribute, $value)
    {
        Type::checkType($value);
    }
}
