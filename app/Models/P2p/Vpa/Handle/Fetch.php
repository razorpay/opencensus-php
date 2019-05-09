<?php

namespace RZP\Models\P2p\Vpa\Handle;

use RZP\Models\P2p\Base;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends Base\Fetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ACTIVE => 'sometimes'
        ]
    ];

    const ACCESSES = [
        self::DEFAULTS => [
            Entity::ACTIVE,
        ],
    ];
}
