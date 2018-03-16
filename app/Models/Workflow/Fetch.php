<?php

namespace RZP\Models\Workflow;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ORG_ID        => 'sometimes|string|max:14',
        ],
    ];

    const ACCESSES = [
        AuthType::ADMIN_AUTH => [
            Entity::ORG_ID,
        ],
    ];
}
