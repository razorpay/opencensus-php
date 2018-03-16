<?php

namespace RZP\Models\Feature;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ENTITY_ID   => 'sometimes|string|max:14',
            Entity::ENTITY_TYPE => 'sometimes|string|max:255',
            Entity::NAME        => 'sometimes|string|max:25'
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::ENTITY_ID,
            Entity::ENTITY_TYPE,
            Entity::NAME,
        ]
    ];
}
