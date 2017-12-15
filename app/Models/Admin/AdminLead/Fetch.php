<?php

namespace RZP\Models\Admin\AdminLead;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ADMIN_ID => 'sometimes|string|max:14',
            Entity::EMAIL    => 'sometimes|email',
            Entity::ORG_ID   => 'sometimes|string|max:14',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::ADMIN_ID,
            Entity::EMAIL,
            Entity::ORG_ID,
        ],
    ];
}
