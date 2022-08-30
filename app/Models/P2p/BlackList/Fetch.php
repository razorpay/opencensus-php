<?php

namespace RZP\Models\P2p\BlackList;

use RZP\Models\P2p\Base;
use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\BankAccount;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends Base\Fetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID                              => 'sometimes|string',
            Entity::TYPE                            => 'sometimes|string',
            Entity::ENTITY_ID                       => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::DEVICE_ID,
        ],
        AuthType::PRIVATE_AUTH => [
            Entity::DEVICE_ID,
            Entity::ENTITY_ID,
        ],
    ];
}
