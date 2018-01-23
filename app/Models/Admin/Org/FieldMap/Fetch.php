<?php

namespace RZP\Models\Admin\Org\FieldMap;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ENTITY_NAME => 'sometimes|string',
            Entity::ORG_ID      => 'sometimes|string|max:14',
        ],
    ];

    const ACCESSES = [
        AuthType::PROXY_AUTH => [
            Entity::ENTITY_NAME,
            Entity::ORG_ID,
        ],
    ];
}
