<?php

namespace RZP\Models\Roles;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Models\Roles\Entity;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            self::EXPAND_EACH            => 'filled|string|in:access_policy',
            Entity::ID                   => 'sometimes|max:19',
            Entity::MERCHANT_ID          => 'sometimes|unsigned_id',
            Entity::NAME                 => 'sometimes|string|max:100',
            Entity::DESCRIPTION          => 'sometimes|string|max:255',
            Entity::TYPE                 => 'sometimes|string|max:50|in:standard,custom',
        ],
    ];

    const ACCESSES = [
        self::DEFAULTS => [
            self::EXPAND_EACH,
            Entity::ID,
            Entity::MERCHANT_ID,
            Entity::NAME,
            Entity::DESCRIPTION,
            Entity::TYPE
        ],
    ];
}
