<?php

namespace RZP\Models\Plan;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::PERIOD      => 'filled|string|max:16|custom',
            Entity::INTERVAL    => 'filled|integer|min:1|max:4000',
            Entity::MERCHANT_ID => 'filled|string|size:14',
            Entity::ITEM_ID     => 'filled|string|min:14|max:19',
        ],
    ];

    const ACCESSES = [
        AuthType::PROXY_AUTH => [
            Entity::PERIOD,
            Entity::INTERVAL,
        ],
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::ITEM_ID,
        ]
    ];

    const SIGNED_IDS = [
        Entity::MERCHANT_ID,
        Entity::ITEM_ID,
    ];


    protected function validatePeriod($attribute, $value)
    {
        Cycle::validatePeriod($value);
    }
}
