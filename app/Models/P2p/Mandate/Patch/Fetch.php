<?php

namespace RZP\Models\P2p\Mandate\Patch;

use RZP\Models\P2p\Base;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends Base\Fetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID                  => 'sometimes|string',
            Entity::MANDATE_ID          => 'sometimes|string',
            Entity::DEVICE_ID           => 'sometimes|string',
            Entity::STATUS              => 'sometimes|string',
            Entity::ACTIVE              => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::ID,
            Entity::MERCHANT_ID,
            Entity::DEVICE_ID,
        ],
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::DEVICE_ID,
            Entity::STATUS,
        ],
        AuthType::DEVICE_AUTH => [
            Entity::STATUS,
            Entity::MANDATE_ID,
            Entity::ACTIVE,
        ]
    ];
}
