<?php

namespace RZP\Models\P2p\BankAccount;

use RZP\Models\P2p\Base;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends Base\Fetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID               => 'sometimes|string',
            Entity::DEVICE_ID        => 'sometimes|string',
            Entity::ACCOUNT_NUMBER   => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::DEVICE_ID,
            Entity::ACCOUNT_NUMBER,
        ],
    ];
}
