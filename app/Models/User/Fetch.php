<?php

namespace RZP\Models\User;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::EMAIL           => 'sometimes|email|max:255',
            Entity::CONTACT_MOBILE  => 'sometimes|contact_syntax',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::EMAIL,
            Entity::CONTACT_MOBILE
        ],
    ];
}
