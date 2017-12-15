<?php

namespace RZP\Models\Admin\Role;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::NAME    => 'sometimes|string',
            Entity::ORG_ID  => 'sometimes',
        ],
    ];

    const ACCESSES = [
        AuthType::PROXY_AUTH => [
            Entity::NAME,
        ],
        AuthType::ADMIN_AUTH => [
            Entity::ORG_ID,
        ],
    ];
}
