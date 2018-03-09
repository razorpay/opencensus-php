<?php

namespace RZP\Models\FileStore;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID         => 'sometimes|alpha_num|max:14',
            Entity::TYPE                => 'sometimes|alpha_dash|max:100',
            Entity::ENTITY_ID           => 'sometimes|alpha_num|max:14',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::TYPE,
            Entity::ENTITY_ID,
        ]
    ];
}
