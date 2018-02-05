<?php

namespace RZP\Models\GeoIP;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        Fetch::DEFAULTS => [
            Entity::IP          => 'filled|ip',
            Entity::CITY        => 'filled|string|max:100',
            Entity::COUNTRY     => 'filled|string|max:4',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::IP,
            Entity::CITY,
            Entity::COUNTRY,
        ],
    ];
}
