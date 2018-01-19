<?php

namespace RZP\Models\Admin\Group;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ORG_ID  => 'sometimes|string',
            Entity::NAME    => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        AuthType::PROXY_AUTH => [
            Entity::ORG_ID,
            Entity::NAME,
        ],
    ];
}
