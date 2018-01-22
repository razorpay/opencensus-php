<?php

namespace RZP\Models\Coupon;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID         => 'sometimes|alpha_num|max:14',
            Entity::ENTITY_ID           => 'sometimes|alpha_num|max:14',
            Entity::ENTITY_TYPE         => 'sometimes|string|in:promotion',
            Entity::CODE                => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::ENTITY_ID,
            Entity::ENTITY_TYPE,
            Entity::CODE,
        ],
    ];
}
