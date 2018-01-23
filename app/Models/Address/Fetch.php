<?php

namespace RZP\Models\Address;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ENTITY_ID   => 'sometimes|alpha_num|size:14',
            Entity::ENTITY_TYPE => 'sometimes|string|max:32',
            Entity::TYPE        => 'sometimes|string|max:32',
            Entity::STATE       => 'sometimes|string|max:64',
            Entity::COUNTRY     => 'sometimes|string|max:64',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::ENTITY_ID,
            Entity::ENTITY_TYPE,
            Entity::TYPE,
            Entity::STATE,
            Entity::COUNTRY,
        ],
    ];
}
