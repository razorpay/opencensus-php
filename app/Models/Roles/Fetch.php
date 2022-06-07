<?php

namespace RZP\Models\Roles;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Models\Roles\Entity;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID                   => 'sometimes|public_id|size:19',
            Entity::MERCHANT_ID          => 'sometimes|string|unsigned_id',
            Entity::NAME                 => 'sometimes|string|max:100',
            Entity::DESCRIPTION          => 'sometimes|string|max:255',
            Entity::TYPE                 => 'sometimes|string|max:50|in:standard,custom',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::ID,
            Entity::MERCHANT_ID,
            Entity::NAME,
            Entity::DESCRIPTION,
            Entity::TYPE
        ],
        AuthType::PROXY_AUTH => [
            Entity::ID,
            Entity::MERCHANT_ID,
            Entity::NAME,
            Entity::DESCRIPTION,
            Entity::TYPE
        ],
        AuthType::PRIVILEGE_AUTH => [
            Entity::ID,
            Entity::MERCHANT_ID,
            Entity::NAME,
            Entity::DESCRIPTION,
            Entity::TYPE
        ],
    ];
}
