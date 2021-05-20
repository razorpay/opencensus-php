<?php

namespace RZP\Models\Reward;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ADVERTISER_ID           => 'sometimes|string|unsigned_id',
            Entity::BRAND_NAME              => 'sometimes|string|max:26',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::ADVERTISER_ID,
            Entity::BRAND_NAME,
        ],
    ];
}
