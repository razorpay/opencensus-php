<?php

namespace RZP\Models\P2p\Vpa;

use RZP\Models\P2p\Base;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends Base\Fetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID                  => 'sometimes|string',
            Entity::DEVICE_ID           => 'sometimes|string',
            Entity::BANK_ACCOUNT_ID     => 'sometimes|string',
            Entity::ACTIVE              => 'sometimes',
            self::DELETED               => 'sometimes',
        ],
    ];

    const ACCESSES = [
        AuthType::DEVICE_AUTH => [
            self::DELETED,
        ],
    ];
}
